<?php
/**
 * command_base Class - Base class for module commands
 *
 * This class provides common functionality for all module command classes.
 * Module classes extend this base class to inherit buffer management capabilities.
 */
class command_base
{
    /**
     * @var string Output buffer for storing generated content
     */
    protected $buffer = '';

    /**
     * Add content to the output buffer
     *
     * @param mixed $content Content to add to buffer
     * @return void
     */
    protected function addBuffer($content)
    {
        $this->buffer .= $content;
    }

    /**
     * Get the current buffer content
     *
     * @return string Current buffer content
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Clear the buffer content
     *
     * @return void
     */
    public function clearBuffer()
    {
        $this->buffer = '';
    }

    /**
     * Get and clear the buffer content
     *
     * @return string Current buffer content before clearing
     */
    public function flushBuffer()
    {
        $content = $this->buffer;
        $this->clearBuffer();
        return $content;
    }
}
?>
