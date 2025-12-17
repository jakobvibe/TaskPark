<?php

namespace App\Security;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TaskVoter extends Voter
{
    public const EDIT = 'edit';
    public const VIEW = 'view';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW])
            && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Task $task */
        $task = $subject;

        return match($attribute) {
            self::EDIT, self::VIEW => $this->isOwner($task, $user),
            default => false,
        };
    }

    private function isOwner(Task $task, User $user): bool
    {
        return $task->getUser()->getId() === $user->getId();
    }
}
