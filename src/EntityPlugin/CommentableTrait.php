<?php namespace Atomino\Molecules\EntityPlugin\Commentable;

use Atomino\Database\Finder\Comparison;
use Atomino\Database\Finder\Filter;
use Atomino\Molecules\Module\Authorizable\AuthorizableInterface;
use Atomino\Molecules\Module\Comment\CommentConverter;
use Atomino\Molecules\Module\Comment\CommenterInterface;
use Atomino\Molecules\Module\Comment\CommentInterface;


trait CommentableTrait {

	/**
	 * @param CommenterInterface|null $user
	 * @param string $commentViewClass
	 * @param string $commenterClass
	 * @param bool $deep
	 * @return CommentConverter
	 */
	public function getConverter(CommenterInterface|null $user, string $commentViewClass, string $commenterClass, bool $deep = true) {
		return new CommentConverter(
			$commentViewClass,
			$commenterClass,
			static::getCommentableDescriptor()->entity,
			$user?->id,
			$this->canModerateComment($user)
		);
	}

//	public function getCommenter(): AuthorizableInterface|null { return (static::getCommentableDescriptor()->userEntity)::getAuthenticated(); }
//	public function commenterHasRole(string $role): bool { return (bool)($this->getCommenter() ?? $this->getCommenter()->hasRole($role)); }


	public function addComment(CommenterInterface|null $user, string $text, ?int $replyId = null, bool $asBot = false): bool {
		if (!$this->canAddComment($user)) return false;
		$comment = new (self::getCommentableDescriptor()->entity)();
		$comment->hostId = $this->id;
		$comment->text = $text;
		$comment->userId = $user->id;
		$comment->replyId = $replyId;
		$comment->status = true;
		$comment->asId = $asBot && $user->canCommentAsBot() ? (self::getCommentableDescriptor()->botId) : null;
		$this->recalculateCommentCache();
		$comment->save();
		return true;
	}
	public function deleteComment(CommenterInterface|null $user, int $commentId): bool {
		if (!$this->canDeleteComment($user, $commentId)) return false;
		$comment = (self::getCommentableDescriptor()->entity)::pick($commentId);
		if (is_null($comment)) return false;
		$comment->delete();
		$this->recalculateCommentCache();
		return true;
	}
	public function hideComment(CommenterInterface|null $user, int $commentId): bool { return $this->setCommentStatus($user, $commentId, false); }
	public function unHideComment(CommenterInterface|null $user, int $commentId): bool { return $this->setCommentStatus($user, $commentId, true); }


	/**
	 * @param int ...$commentIds
	 * @return CommentInterface[]
	 */
	public function fetchComments(int ...$commentIds): array {
		return (static::getCommentableDescriptor()->entity)::collect($commentIds);
	}

	public function getComment(CommenterInterface|null $user, int $commentId): CommentInterface|null {
		if (!$this->canReadComment($user)) return null;
		$comment = (static::getCommentableDescriptor()->entity)::pick($commentId);
		if ($comment->hostId !== $this->id) return null;
		if (!$comment->status && !$this->canModerateComment($user)) return null;
		return $comment;
	}
	/**
	 * @param int $page
	 * @param int $limit
	 * @return CommentInterface[]
	 */
	public function getComments(CommenterInterface|null $user, int $page, int $limit): array {
		if (!$this->canReadComment($user)) return [];
		return (self::getCommentableDescriptor()->entity)::search(
			Filter::where((new Comparison('hostId'))->is($this->id))
			      ->and(!$this->canModerateComment($user) ? (new Comparison('status'))->is(true) : false)
		)->desc('created')->page($limit, $page)
			;
	}


	protected function canReadComment(CommenterInterface|null $user): bool { return true; }
	protected function canAddComment(CommenterInterface|null $user): bool { return !is_null($user) && $user->canAddComment(); }
	protected function canModerateComment(CommenterInterface|null $user): bool { return !is_null($user) && $user->canModerateComment(); }
	protected function canCommentAsBot(CommenterInterface|null $user): bool { return !is_null($user) && $user->canModerateComment(); }
	protected function canDeleteComment(CommenterInterface|null $user, int $commentId): bool {
		if (is_null($user) || !$user->canAddComment()) return false;
		if ($user->canModerateComment()) return true;
		$timeout = static::getCommentableDescriptor()->deleteTimeout;
		if ($timeout === false) return false;
		/** @var CommentInterface $comment */
		$comment = (self::getCommentableDescriptor()->entity)::pick($commentId);
		if ($comment->userId !== $user->id) return false;
		if ($timeout === 0) return true;
		return $comment->created->getTimestamp() + $timeout < time();
	}

	protected static function getCommentableDescriptor(): Commentable {
		static $cd = null;
		if (is_null($cd)) $cd = Commentable::get(new \ReflectionClass(static::class));
		return $cd;
	}

	protected function recalculateCommentCache() {
		$count = (self::getCommentableDescriptor()->entity)::search($filter = Filter::where((new Comparison('hostId'))->is($this->id)))->count();
		$publicCount = (self::getCommentableDescriptor()->entity)::search($filter->and((new Comparison('status'))->is(true)))->count();
		$last = (self::getCommentableDescriptor()->entity)::search($filter)->desc('id')->pick();
		$lastId = $last ? $last->id : null;
		$lastCreated = $last ? $last->created : null;
		$this->commentCache = [
			'count'       => $count,
			'publicCount' => $publicCount,
			'lastId'      => $lastId,
			'lastCreated' => $lastCreated,
		];
		$this->handleEvent(CommentableInterface::COMMENTS_RECALUCLATED, $this->commentCache);
		$this->save();
	}

	protected function setCommentStatus(CommenterInterface|null $user, int $commentId, bool $status) {
		if (!$this->canModerateComment($user)) return false;
		$comment = (self::getCommentableDescriptor()->entity)::pick($commentId);
		if (is_null($comment)) return false;
		$comment->status = $status;
		$comment->save();
		$this->recalculateCommentCache();
	}
}