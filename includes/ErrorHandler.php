<?php
require_once __DIR__ . '/../config.php';

class ErrorHandler {
    private static $logFile;
    
    public static function init() {
        self::$logFile = LOG_PATH . '/error_' . date('Y-m-d') . '.log';
        
        // Set error and exception handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        
        // Ensure log directory exists
        if (!file_exists(dirname(self::$logFile))) {
            mkdir(dirname(self::$logFile), 0755, true);
        }
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $error = [
            'type' => self::getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'time' => date('Y-m-d H:i:s'),
            'user' => isset($_SESSION['user_id']) ? $_SESSION['username'] : 'Guest',
            'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown URL'
        ];
        
        self::logError($error);
        
        if (DEBUG_MODE) {
            echo self::formatErrorForDisplay($error);
        } else {
            echo "An error occurred. Please try again later.";
        }
        
        return true;
    }
    
    public static function handleException($exception) {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'time' => date('Y-m-d H:i:s'),
            'user' => isset($_SESSION['user_id']) ? $_SESSION['username'] : 'Guest',
            'url' => $_SERVER['REQUEST_URI'] ?? 'Unknown URL'
        ];
        
        self::logError($error);
        
        if (DEBUG_MODE) {
            echo self::formatErrorForDisplay($error);
        } else {
            echo "An error occurred. Please try again later.";
        }
    }
    
    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
    
    private static function logError($error) {
        $logMessage = sprintf(
            "[%s] %s: %s in %s on line %d\nUser: %s\nURL: %s\n%s\n",
            $error['time'],
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line'],
            $error['user'],
            $error['url'],
            isset($error['trace']) ? "Stack trace:\n" . $error['trace'] : ''
        );
        
        error_log($logMessage, 3, self::$logFile);
    }
    
    private static function formatErrorForDisplay($error) {
        $output = "<div style='background:#f8d7da;color:#721c24;padding:10px;margin:10px;border:1px solid #f5c6cb;border-radius:4px'>";
        $output .= "<h3>Error Details</h3>";
        $output .= "<p><strong>Type:</strong> " . htmlspecialchars($error['type']) . "</p>";
        $output .= "<p><strong>Message:</strong> " . htmlspecialchars($error['message']) . "</p>";
        $output .= "<p><strong>File:</strong> " . htmlspecialchars($error['file']) . "</p>";
        $output .= "<p><strong>Line:</strong> " . htmlspecialchars($error['line']) . "</p>";
        if (isset($error['trace'])) {
            $output .= "<p><strong>Stack Trace:</strong></p>";
            $output .= "<pre>" . htmlspecialchars($error['trace']) . "</pre>";
        }
        $output .= "</div>";
        return $output;
    }
    
    private static function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR:
                return 'Fatal Error';
            case E_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse Error';
            case E_NOTICE:
                return 'Notice';
            case E_CORE_ERROR:
                return 'Core Error';
            case E_CORE_WARNING:
                return 'Core Warning';
            case E_COMPILE_ERROR:
                return 'Compile Error';
            case E_COMPILE_WARNING:
                return 'Compile Warning';
            case E_USER_ERROR:
                return 'User Error';
            case E_USER_WARNING:
                return 'User Warning';
            case E_USER_NOTICE:
                return 'User Notice';
            case E_STRICT:
                return 'Strict Notice';
            case E_RECOVERABLE_ERROR:
                return 'Recoverable Error';
            case E_DEPRECATED:
                return 'Deprecated';
            case E_USER_DEPRECATED:
                return 'User Deprecated';
            default:
                return 'Unknown Error';
        }
    }
} 