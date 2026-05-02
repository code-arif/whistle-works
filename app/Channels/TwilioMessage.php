<?php

namespace App\Channels;

/**
 * Simple value object that holds the SMS body text.
 *
 * Fluent usage:
 *   (new TwilioMessage)->content('Hello, World!');
 */
class TwilioMessage
{
    public string $content = '';

    /**
     * Set the SMS body text.
     */
    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }
}
