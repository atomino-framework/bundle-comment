<?php namespace Atomino\Molecules\EntityPlugin\Commentable;

use Atomino\Database\Finder\Comparison;
use Atomino\Database\Finder\Filter;
use Atomino\Molecules\Module\Authorizable\AuthorizableInterface;
use Atomino\Molecules\Module\Comment\CommentConverter;
use Atomino\Molecules\Module\Comment\CommentInterface;


trait CommentableTrait {

	public static function getConverter(string $commentViewClass, bool $deep = true) {
		return new CommentConverter(
			$commentViewClass,
			self::getCommentableDescriptor()->userEntity,
			self::getCommentableDescriptor()->entity,
			self::getCommentableDescriptor()->roleModerator
		);
	}

	public function getCommenter(): AuthorizableInterface|null { return (static::getCommentableDescriptor()->userEntity)::getAuthenticated(); }

	public static function getCommentableDescriptor(): Commentable {
		static $cd = null;
		if (is_null($cd)) $cd = Commentable::get(new \ReflectionClass(static::class));
		return $cd;
	}

	public function commenterHasRole(string $role): bool { return (bool)($this->getCommenter() ?? $this->getCommenter()->hasRole($role)); }

	public function canReadComment(): bool { return true; }
	public function canAddComment(): bool { return $this->commenterHasRole(self::getCommentableDescriptor()->roleCommenter); }
	public function canModerateComment(): bool { return $this->commenterHasRole(self::getCommentableDescriptor()->roleModerator); }
	public function canCommentAsBot(): bool { return $this->commenterHasRole(self::getCommentableDescriptor()->roleModerator); }
	public function canDeleteComment(int $commentId): bool {
		if ($this->canModerateComment()) return true;
		$timeout = static::getCommentableDescriptor()->deleteTimeout;
		if ($timeout === false) return false;
		if (!$this->commenterHasRole(self::getCommentableDescriptor()->roleCommenter)) return false;
		/** @var CommentInterface $comment */
		$comment = (self::getCommentableDescriptor()->entity)::pick($commentId);
		if ($comment->userId !== $this->getCommenter()?->id) return false;
		if ($timeout === 0) return true;
		return $comment->created->getTimestamp() + $timeout < time();
	}

	public function addComment(string $text, ?int $replyId = null, bool $asBot = false): bool {
		if (!$this->canAddComment()) return false;
		$comment = new (self::getCommentableDescriptor()->entity)();
		$comment->hostId = $this->id;
		$comment->text = $text;
		$comment->userId = $this->getCommenter()->id;
		$comment->replyId = $replyId;
		$comment->status = true;
		$comment->asId = $asBot && $this->canCommentAsBot() ? (self::getCommentableDescriptor()->botId) : null;
		$this->recalculateCommentCache();
		$comment->save();
		return true;
	}
	public function deleteComment(int $commentId): bool {
		if (!$this->canDeleteComment($commentId)) return false;
		$comment = (self::getCommentableDescriptor()->entity)::pick($commentId);
		if (is_null($comment)) return false;
		$comment->delete();
		$this->recalculateCommentCache();
		return true;
	}
	public function hideComment(int $commentId): bool { return $this->setCommentStatus($commentId, false); }
	public function unHideComment(int $commentId): bool { return $this->setCommentStatus($commentId, true); }
	public function setCommentStatus(int $commentId, bool $status) {
		if (!$this->canModerateComment()) return false;
		$comment = (self::getCommentableDescriptor()->entity)::pick($commentId);
		if (is_null($comment)) return false;
		$comment->status = $status;
		$comment->save();
		$this->recalculateCommentCache();
	}

	/**
	 * @param int ...$commentIds
	 * @return CommentInterface[]
	 */
	public function fetchComments(int ...$commentIds): array { return (static::getCommentableDescriptor()->entity)::collect($commentIds); }

	public function getComment(int $commentId): CommentInterface|null {
		if (!$this->canReadComment()) return null;
		$comment = (static::getCommentableDescriptor()->entity)::pick($commentId);
		if ($comment->hostId !== $this->id) return null;
		if (!$comment->status && !$this->canModerateComment()) return null;
		return $comment;
	}
	/**
	 * @param int $page
	 * @param int $limit
	 * @return CommentInterface[]
	 */
	public function getComments(int $page, int $limit): array {
		if (!$this->canReadComment()) return [];
		return (self::getCommentableDescriptor()->entity)::search(
			Filter::where((new Comparison('hostId'))->is($this->id))
			      ->and(!$this->canModerateComment() ? (new Comparison('status'))->is(true) : false)
		)->desc('created')->page($limit, $page)
			;
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
}