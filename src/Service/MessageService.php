<?php

namespace App\Service;

use App\Entity\SupportiveMessage;
use App\Repository\SupportiveMessageRepository;

class MessageService
{
    // Fallback messages if database is empty
    private const FALLBACK_MESSAGES = [
        SupportiveMessage::CATEGORY_WELCOME => [
            "Welcome back! Let's make today count, one small step at a time.",
            "Good to see you! Remember: progress, not perfection.",
            "Hey there! Your future self will thank you for showing up today.",
        ],
        SupportiveMessage::CATEGORY_ENCOURAGEMENT => [
            "Take it one task at a time. You've got this!",
            "Small steps lead to big changes.",
            "Every task completed is a victory.",
        ],
        SupportiveMessage::CATEGORY_COMPLETION => [
            "Nice work! Every completed task is a victory.",
            "You did it! Small wins lead to big changes.",
            "Task complete! You're building momentum.",
        ],
        SupportiveMessage::CATEGORY_CALMING => [
            "Feeling overwhelmed? Focus on just one task. The rest can wait.",
            "Remember to breathe. You don't have to do everything today.",
            "It's okay to move tasks to another day. Be kind to yourself.",
        ],
    ];

    public function __construct(
        private SupportiveMessageRepository $messageRepository,
    ) {
    }

    /**
     * Get a random message for the dashboard
     */
    public function getWelcomeMessage(): string
    {
        $message = $this->messageRepository->findRandomByCategory(SupportiveMessage::CATEGORY_WELCOME);

        if ($message) {
            return $message->getMessage();
        }

        // Fallback to hardcoded messages
        $fallbacks = self::FALLBACK_MESSAGES[SupportiveMessage::CATEGORY_WELCOME];
        return $fallbacks[array_rand($fallbacks)];
    }

    /**
     * Get an encouragement message (for general display)
     */
    public function getEncouragementMessage(): string
    {
        $message = $this->messageRepository->findRandomByCategory(SupportiveMessage::CATEGORY_ENCOURAGEMENT);

        if ($message) {
            return $message->getMessage();
        }

        $fallbacks = self::FALLBACK_MESSAGES[SupportiveMessage::CATEGORY_ENCOURAGEMENT];
        return $fallbacks[array_rand($fallbacks)];
    }

    /**
     * Get a completion message (shown when task is completed)
     */
    public function getCompletionMessage(): string
    {
        $message = $this->messageRepository->findRandomByCategory(SupportiveMessage::CATEGORY_COMPLETION);

        if ($message) {
            return $message->getMessage();
        }

        $fallbacks = self::FALLBACK_MESSAGES[SupportiveMessage::CATEGORY_COMPLETION];
        return $fallbacks[array_rand($fallbacks)];
    }

    /**
     * Get a calming message (for when user has many tasks)
     */
    public function getCalmingMessage(): string
    {
        $message = $this->messageRepository->findRandomByCategory(SupportiveMessage::CATEGORY_CALMING);

        if ($message) {
            return $message->getMessage();
        }

        $fallbacks = self::FALLBACK_MESSAGES[SupportiveMessage::CATEGORY_CALMING];
        return $fallbacks[array_rand($fallbacks)];
    }

    /**
     * Get appropriate message based on task count
     */
    public function getContextualMessage(int $taskCount): string
    {
        if ($taskCount > 10) {
            return $this->getCalmingMessage();
        }

        return $this->getEncouragementMessage();
    }
}
