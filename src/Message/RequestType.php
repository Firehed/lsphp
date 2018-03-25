<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Message;

interface RequestType
{
    const INITIALIZE = 'initialize';
    const TEXTDOCUMENT_DIDOPEN = 'textDocument/didOpen';
    const TEXTDOCUMENT_DIDCHANGE = 'textDocument/didChange';
    const WINDOW_LOGMESSAGE = 'window/logMessage';
}
