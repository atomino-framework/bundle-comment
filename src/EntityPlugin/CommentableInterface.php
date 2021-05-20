<?php namespace Atomino\Molecules\EntityPlugin\Commentable;

use Atomino\Molecules\Module\Authorizable\AuthorizableInterface;

interface CommentableInterface {

	const COMMENTS_RECALUCLATED = "COMMENTS_RECALUCLATED";

	public function getCommenter(): AuthorizableInterface|null;
	public function commenterHasRole(string $role): bool;
	public function canReadComment(): bool;
	public function canAddComment(): bool;
	public function canDeleteComment(int $commentId): bool;
	public function canModerateComment(): bool;
	public function canCommentAsBot(): bool;
	public function addComment(string $text, int|null $replyId = null, bool $asBot = false): bool;
	public function deleteComment(int $commentId): bool;
	public function hideComment(int $commentId): bool;
	public function unHideComment(int $commentId): bool;
	/**
	 * @param int $page
	 * @param int $limit
	 * @return CommentInterface[]
	 */
	public function getComments(int $page, int $limit): array;
}