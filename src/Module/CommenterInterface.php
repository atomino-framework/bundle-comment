<?php namespace Atomino\Molecules\Module\Comment;

/**
 * @property-read int $id
 */
interface CommenterInterface {
	public function canAddComment():bool;
	public function canModerateComment():bool;
	public function canCommentAsBot():bool;
}