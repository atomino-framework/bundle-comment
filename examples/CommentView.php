<?php namespace Application\Modules;

use Application\Entity\User;
use Atomino\Bundle\Comment\CommentInterface;
use Atomino\Bundle\Comment\CommentViewInterface;


class CommentView implements CommentViewInterface {
	public \DateTime $created;
	public bool $status;
	public string $text;
	public int|null $replyId;
	public CommentView|null $reply = null;
	public string|null $avatar;
	public string $nick;

	/**
	 * CommentView constructor.
	 * @param CommentInterface $comment
	 * @param bool $deep
	 * @param CommentInterface $commentEntity
	 */
	public function __construct(CommentInterface $comment, int|null $userId, bool $isModerator, bool $deep = false, string|null $commentEntity = null) {
		$this->created = $comment->created;
		$this->status = $comment->status;
		$this->text = $comment->text;
		$user = User::pick($comment->userId);
		$this->replyId = $comment->replyId;

		if($this->replyId && $deep) $this->reply = new self(($commentEntity)::pick($this->replyId), $userId, $isModerator);
		$this->avatar = $user->avatar?->first->image->crop(128,128)->webp;
		$this->nick = $user->name ?? 'anonymous';
	}
}