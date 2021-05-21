<?php namespace Atomino\Molecules\Module\Comment;

interface CommentViewInterface {
	public function __construct(CommentInterface $comment, int|null $userId, bool $isModerator, bool $deep = false, string|null $commentEntity = null);
}