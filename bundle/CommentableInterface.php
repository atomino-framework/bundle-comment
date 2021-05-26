<?php namespace Atomino\Bundle\Comment;

interface CommentableInterface {

	const COMMENTS_RECALUCLATED = "COMMENTS_RECALUCLATED";

	public function addComment(CommenterInterface|null $user, string $text, int|null $replyId = null, bool $asBot = false): bool;
	public function deleteComment(CommenterInterface|null $user, int $commentId): bool;
	public function hideComment(CommenterInterface|null $user, int $commentId): bool;
	public function unHideComment(CommenterInterface|null $user, int $commentId): bool;
	/**
	 * @param int $page
	 * @param int $limit
	 * @return CommentInterface[]
	 */
	public function getComments(CommenterInterface|null $user, int $page, int $limit): array;
	/**
	 * @param int ...$commentIds
	 * @return CommentInterface[]
	 */
	public function getComment(CommenterInterface|null $user, int $commentId): CommentInterface|null;
	public function fetchComments(int ...$commentIds): array;
}