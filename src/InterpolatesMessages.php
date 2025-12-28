<?php

/*
 * This file is part of vaibhavpandeyvpz/drishti package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source file in the file LICENSE.
 */

namespace Drishti;

/**
 * Trait for message interpolation functionality.
 *
 * Provides a shared implementation for interpolating context values
 * into message placeholders. Used by formatters to avoid code duplication.
 *
 * @author  Vaibhav Pandey <contact@vaibhavpandey.com>
 */
trait InterpolatesMessages
{
    /**
     * Interpolates context values into the message placeholders.
     *
     * Replaces placeholders in the form {key} with the corresponding value
     * from the context array. If a placeholder is not found in the context,
     * it remains unchanged in the message.
     *
     * @param  string  $message  The message with placeholders
     * @param  array<string, mixed>  $context  The context data
     * @return string The interpolated message
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            // Skip exception as it's handled separately
            if ($key === 'exception' && $value instanceof \Throwable) {
                continue;
            }

            // Convert value to string representation
            $replace['{'.$key.'}'] = match (true) {
                \is_object($value) && \method_exists($value, '__toString') => (string) $value,
                \is_object($value) => \get_class($value),
                \is_array($value) => \json_encode($value, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE),
                \is_resource($value) => \get_resource_type($value),
                \is_bool($value) => $value ? 'true' : 'false',
                $value === null => 'null',
                default => (string) $value,
            };
        }

        return \strtr($message, $replace);
    }
}
