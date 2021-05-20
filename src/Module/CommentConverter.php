<?php namespace Atomino\Molecules\Module\Comment;


class CommentConverter {

	public function __construct(protected string $commentViewClass, protected string $userClass, protected string $commentClass, protected string $moderatorRole = "moderator") {}

	protected function isModerator(){
		$user = ($this->userClass)::getAuthenticated();
		if(is_null($user)) return false;
		return $user->hasRole($this->moderatorRole);
	}
	/**
	 * @param CommentInterface[] $comments
	 * @param bool $deep
	 * @return CommentViewInterface[]
	 */
	public function convertComments(array $comments, bool $deep=true):array {
		if(count($comments) === 0) return [];
		$replyIds = [];
		$userIds = [];
		array_walk($comments, function (CommentInterface $comment) use ($replyIds, $userIds) {
			$userIds[] = $comment->userId;
			if ($comment->replyId !== null) $replyIds[] = $comment->replyId;
		});
		if(count($replyIds) && $deep){
			$replyIds = array_unique($replyIds);
			$replys = ($this->commentClass)::collect($replyIds);
			array_walk($replys, function (CommentInterface $comment) use($userIds){ $userIds[]=$comment->userId;});
		}
		$userIds = array_unique($userIds);
		($this->userClass)::collect($userIds);
		$converted = [];
		foreach ($comments as $comment){
			$converted[] = new ($this->commentViewClass)($comment,  $deep, $comment::class);
		}
		return $converted;
	}
}


