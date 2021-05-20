<?php namespace Atomino\Molecules\Module\Comment;

use Atomino\Entity\EntityInterface;

/**
 * @property int $id
 * @property string $text
 * @property bool $status
 * @property-read \DateTime $created
 * @property int $userId
 * @property int $asId
 * @property int $hostId
 * @property int $replyId
 */
interface CommentInterface extends EntityInterface {
}