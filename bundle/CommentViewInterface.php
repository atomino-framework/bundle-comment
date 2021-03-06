<?php namespace Atomino\Bundle\Comment;

interface CommentViewInterface {
	public function __construct(CommentInterface $comment, int|null $userId, bool $isModerator, bool $deep = false, string|null $commentEntity = null);
}