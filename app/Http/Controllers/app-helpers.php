<?php

    if (!function_exists('isExamManager')) {
        function isExamManager() {
            if (Auth::check()) {
                if(Auth::user()->can('exam-system')){
                    return true;
                }
            }
            return false;
        }
    }


    /*
      * NOTE: This helper function is deprecated as of 24 JUN 2016 instead use engineReaction of CoreEngine function
      *
      * Send reaction from Engine mostly to Controllers
      *
      * @param Array    $reactionCode  - Reaction from Repo
      * @param Array    $data          - Array of data if needed
      * @param String   $message       - Message if any
      *
      * @return array
      *---------------------------------------------------------------- */

    if (!function_exists('__engineReaction')) {
        function __engineReaction($reactionCode, $message = null, $data = null)
        {
            if (is_array($reactionCode) === true) {
                $message = $reactionCode[2];
                $data = (is_array($data) and is_array($reactionCode[1]))
                                        ? array_merge($reactionCode[1], $data)
                                        : (empty($reactionCode[1])
                                            ? (empty($data) ? null : $data)
                                            : $reactionCode[1]);

                $reactionCode = $reactionCode[0];
            }

            if (__isValidReactionCode($reactionCode) === true) {
                return [
                    'reaction_code' => (integer) $reactionCode,
                    'message' => $message,
                    'data' => $data,
                ];
            }

            throw new Exception('__engineReaction:: Invalid Reaction Code!!');
        }
    }

    /*
      * Customized isEmpty
      *
      * @param Integer  $reactionCode  - Reaction Code
      *
      * @return bool
      *---------------------------------------------------------------- */

    if (!function_exists('__isValidReactionCode')) {
        function __isValidReactionCode($reactionCode)
        {
            if (is_integer($reactionCode) === true
                and array_key_exists($reactionCode,
                    config('__tech.reaction_codes')) === true) {
                return true;
            }

            return false;
        }
    }

    /*
    * get youtube url
    *
    * @param string $code
    *
    * @return string
    *---------------------------------------------------------------- */
    if (!function_exists('getYoutubeUrl')) {
        function getYoutubeUrl($code)
        {
            return 'http://www.youtube.com/embed/'.$code;
        }
    }

    /*
    * get string to hex
    *
    * @param string $code
    *
    * @return string
    *---------------------------------------------------------------- */
    if (!function_exists('strToHex')) {
        function strToHex($string){
            $hex = '';
            for ($i=0; $i<strlen($string); $i++){
                $ord = ord($string[$i]);
                $hexCode = dechex($ord);
                $hex .= substr('0'.$hexCode, -2);
            }
            return strToUpper($hex);
        }

    }
    
    /*
    * get hex to string
    *
    * @param string $code
    *
    * @return string
    *---------------------------------------------------------------- */
    if (!function_exists('hexToStr')) {
        function hexToStr($hex){
            $string='';
            for ($i=0; $i < strlen($hex)-1; $i+=2){
                $string .= chr(hexdec($hex[$i].$hex[$i+1]));
            }
            return $string;
        }
    }
    

    if (!function_exists('datetostr_from')) {
        function datetostr_from($code)
        {
            return substr($code,0,4).
                substr($code,5,2).
                substr($code,8,2).'0000';
        }
    }

    if (!function_exists('datetostr_to')) {
        function datetostr_to($code)
        {
            return substr($code,0,4).
                substr($code,5,2).
                substr($code,8,2).'2359';
        }
    }

    if (!function_exists('strtodate')) {
        function strtodate($code)
        {
            if (strlen($code) === 8) {
                return \DateTime::createFromFormat('Ymd', $code)->format('d/m/Y');
            } elseif (strlen($code) === 12) {
                return \DateTime::createFromFormat('YmdHi', $code)->format('d/m/Y');
            } elseif (strlen($code) === 14) {
                return \DateTime::createFromFormat('YmdHis', $code)->format('d/m/Y');
            } else {
                return null; // Hoặc xử lý theo cách khác nếu cần
            }
        }
    }
    
    if (!function_exists('strtodatetime')) {
        function strtodatetime($code)
        {
            if (strlen($code) === 8) {
                return \DateTime::createFromFormat('Ymd', $code)->format('d/m/Y');
            } elseif (strlen($code) === 12) {
                return \DateTime::createFromFormat('YmdHi', $code)->format('d/m/Y H:i');
            } elseif (strlen($code) === 14) {
                return \DateTime::createFromFormat('YmdHis', $code)->format('d/m/Y H:i');
            } else {
                return null; // Hoặc xử lý theo cách khác nếu cần
            }
        }
    }

    if (!function_exists('getParam')) {
        function getParam($code)
        {
            return \App\Models\BHYT\sys_param::select('param_value')
                ->where('param_code', $code)->first();
        }
    }

    if (!function_exists('dob')) {
        function dob($dob)
        {
            if (substr($dob, 4, 4) === '0000') {
                 return substr($dob, 0, 4);
            }
            if (strlen($dob) === 8) {
                return \DateTime::createFromFormat('Ymd', $dob)->format('d/m/Y');
            } elseif (strlen($dob) === 12) {
                return \DateTime::createFromFormat('YmdHi', $dob)->format('d/m/Y');
            } elseif (strlen($dob) === 14) {
                return \DateTime::createFromFormat('YmdHis', $dob)->format('d/m/Y');
            } else {
                return null; // Hoặc xử lý theo cách khác nếu cần
            }
        }
    }

    if (!function_exists('validateDataStructure')) {
        function validateDataStructure($data, $expectedStructure)
        {
            foreach ($expectedStructure as $key) {
                if (!property_exists($data, $key)) {
                    return false;
                }
            }
            return true;
        }
    }

    if (!function_exists('formatDescription')) {
        function formatDescription(string $text): string
        {
            if (strpos($text, "\n") !== false) {
                return $text . ' (có ký tự xuống dòng)';
            }
            return $text;
        }
    }
    

    if (!function_exists('getFormattedSuggestion')) {
        /**
         * Lấy thông báo từ config và thay thế các placeholder bằng giá trị thực tế.
         *
         * @param string $key - Key trong config qd130xml_suggestions
         * @param array $placeholders - Mảng các giá trị cần thay thế cho các placeholder
         * @return string - Chuỗi thông báo đã được thay thế
         */
        function getFormattedSuggestion($key, $placeholders = [])
        {
            // Lấy thông báo từ file config
            $message = config("qd130xml_suggestions.$key", config("qd130xml_suggestions.general"));

            // Thay thế các placeholder bằng giá trị thực tế
            foreach ($placeholders as $placeholder => $value) {
                $message = str_replace('{' . $placeholder . '}', $value, $message);
            }

            return $message;
        }
    }