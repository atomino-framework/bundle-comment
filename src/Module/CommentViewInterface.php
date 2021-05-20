<?php namespace Atomino\Molecules\Module\Comment;

interface CommentViewInterface{
	public function __construct(CommentInterface $comment, bool $deep = false, string|null $commentEntity = null);
}