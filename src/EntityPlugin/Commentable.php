<?php namespace Atomino\Molecules\EntityPlugin\Commentable;

use Atomino\Entity\Generator\CodeWriter;
use Atomino\Entity\Plugin\Plugin;
use Atomino\Molecules\Modules\Comment\CommentInterface;

/**
 * Class Commentable
 * @property CommentInterface $entity
 */
#[\Attribute]
class Commentable extends Plugin {
	public function __construct(
		public string $entity,
		public string $userEntity,
		public string $roleCommenter = 'comment',
		public string $roleModerator = 'moderator',
		public string $cacheField = 'commentCache',
		public int|bool $deleteTimeout = false,
		public int|null $botId = null
	) {

	}

	public function generate(\ReflectionClass $ENTITY, CodeWriter $codeWriter){
		$codeWriter->addInterface(CommentableInterface::class);
		$codeWriter->addAttribute("#[RequiredField( '" . $this->cacheField . "', \Atomino\Entity\Field\JsonField::class )]");
	}

	public function getTrait(): string|null{ return CommentableTrait::class; }
}