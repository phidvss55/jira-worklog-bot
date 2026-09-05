<?php

namespace App\Services\GoogleChat;

final class GoogleChatCommandParser
{
    public function parse(string $command): ParsedGoogleChatCommand
    {
        $arguments = preg_split('/\s+/u', trim($command));

        if ($arguments === false || $arguments === [''] || strcasecmp($arguments[0], '/log') !== 0) {
            throw new InvalidGoogleChatCommandException('Unsupported command.');
        }

        $argumentCount = count($arguments);

        if ($argumentCount < 3 || $argumentCount > 5) {
            throw new InvalidGoogleChatCommandException('Invalid /log command structure.');
        }

        if ($argumentCount === 5 && str_contains($arguments[3], ':')) {
            throw new InvalidGoogleChatCommandException('The /log command has too many arguments.');
        }

        return new ParsedGoogleChatCommand(
            ticket: $arguments[1],
            duration: $arguments[2],
            date: $argumentCount === 5 ? $arguments[3] : null,
            time: match ($argumentCount) {
                4 => $arguments[3],
                5 => $arguments[4],
                default => null,
            },
        );
    }
}
