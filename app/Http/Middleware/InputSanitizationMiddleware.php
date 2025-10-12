<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InputSanitizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Sanitize input data
        $this->sanitizeInput($request);

        return $next($request);
    }

    /**
     * Sanitize input data
     *
     * @param Request $request
     * @return void
     */
    private function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->recursiveSanitize($input);
        
        // Replace the request data with sanitized data
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array data
     *
     * @param mixed $data
     * @return mixed
     */
    private function recursiveSanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'recursiveSanitize'], $data);
        }

        if (is_string($data)) {
            return $this->sanitizeString($data);
        }

        return $data;
    }

    /**
     * Sanitize string input
     *
     * @param string $input
     * @return string
     */
    private function sanitizeString(string $input): string
    {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Remove excessive whitespace
        $input = preg_replace('/\s+/', ' ', $input);
        
        // Check for malicious patterns BEFORE HTML encoding
        $maliciousPatterns = [
            // XSS patterns
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>.*?<\/embed>/is',
            '/<form[^>]*>.*?<\/form>/is',
            '/<input[^>]*>/i',
            '/<textarea[^>]*>.*?<\/textarea>/is',
            '/<select[^>]*>.*?<\/select>/is',
            '/<option[^>]*>.*?<\/option>/is',
            '/<applet[^>]*>.*?<\/applet>/is',
            '/<meta[^>]*>/i',
            '/<link[^>]*>/i',
            '/<style[^>]*>.*?<\/style>/is',
            '/<title[^>]*>.*?<\/title>/is',
            '/<head[^>]*>.*?<\/head>/is',
            '/<body[^>]*>.*?<\/body>/is',
            '/<html[^>]*>.*?<\/html>/is',
            '/<xml[^>]*>.*?<\/xml>/is',
            '/<php[^>]*>.*?<\/php>/is',
            '/<asp[^>]*>.*?<\/asp>/is',
            '/<jsp[^>]*>.*?<\/jsp>/is',
            '/<cgi[^>]*>.*?<\/cgi>/is',
            '/<perl[^>]*>.*?<\/perl>/is',
            '/<python[^>]*>.*?<\/python>/is',
            '/<ruby[^>]*>.*?<\/ruby>/is',
            '/<shell[^>]*>.*?<\/shell>/is',
            '/<bash[^>]*>.*?<\/bash>/is',
            '/<cmd[^>]*>.*?<\/cmd>/is',
            '/<powershell[^>]*>.*?<\/powershell>/is',
            '/<wscript[^>]*>.*?<\/wscript>/is',
            '/<cscript[^>]*>.*?<\/cscript>/is',
            '/<vbscript[^>]*>.*?<\/vbscript>/is',
            '/<javascript[^>]*>.*?<\/javascript>/is',
            
            // Event handlers
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/onfocus\s*=/i',
            '/onblur\s*=/i',
            '/onchange\s*=/i',
            '/onsubmit\s*=/i',
            '/onreset\s*=/i',
            '/onkeydown\s*=/i',
            '/onkeyup\s*=/i',
            '/onkeypress\s*=/i',
            '/onmousedown\s*=/i',
            '/onmouseup\s*=/i',
            '/onmousemove\s*=/i',
            '/onmouseout\s*=/i',
            '/onmouseenter\s*=/i',
            '/onmouseleave\s*=/i',
            '/oncontextmenu\s*=/i',
            '/ondblclick\s*=/i',
            '/onabort\s*=/i',
            '/onbeforeunload\s*=/i',
            '/onhashchange\s*=/i',
            '/onmessage\s*=/i',
            '/onoffline\s*=/i',
            '/ononline\s*=/i',
            '/onpagehide\s*=/i',
            '/onpageshow\s*=/i',
            '/onpopstate\s*=/i',
            '/onresize\s*=/i',
            '/onscroll\s*=/i',
            '/onstorage\s*=/i',
            '/onunload\s*=/i',
            
            // Protocol handlers
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:/i',
            '/file\s*:/i',
            
            // SQL injection patterns
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\balter\b.*\btable\b)/i',
            '/(\bcreate\b.*\btable\b)/i',
            '/(\bexec\b.*\b\()/i',
            '/(\bexecute\b.*\b\()/i',
        ];
        
        // Check for malicious patterns and block them
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return '[BLOCKED]';
            }
        }
        
        // Basic XSS protection - HTML encode the input
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
}
