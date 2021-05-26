<?php namespace Atomino\Bundle\Comment;


use Atomino\Carbon\Plugins\Comment\CommentableInterface;

class CommentConverter {

	public function __construct(
		protected string $commentViewClass,
		protected string $userClass,
		protected string $commentClass,
		protected int|null $userId,
		protected bool $isModerator
	) {
	}

	/**
	 * @param CommentInterface[] $comments
	 * @param bool $deep
	 * @return CommentViewInterface[]
	 */
	public function convertComments(array $comments, bool $deep = true): array {
		if (count($comments) === 0) return [];
		$replyIds = [];
		$userIds = [];
		array_walk($comments, function (CommentInterface $comment) use ($replyIds, $userIds) {
			$userIds[] = $comment->userId;
			if ($comment->replyId !== null) $replyIds[] = $comment->replyId;
		});
		if (count($replyIds) && $deep) {
			$replyIds = array_unique($replyIds);
			$replys = ($this->commentClass)::collect($replyIds);
			array_walk($replys, function (CommentInterface $comment) use ($userIds) { $userIds[] = $comment->userId; });
		}
		$userIds = array_unique($userIds);
		($this->userClass)::collect($userIds);
		$converted = [];
		foreach ($comments as $comment) {
			$converted[] = new ($this->commentViewClass)($comment, $this->userId, $this->isModerator, $deep, $this->commentClass);
		}
		return $converted;
	}
}

