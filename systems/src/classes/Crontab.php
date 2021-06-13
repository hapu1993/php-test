<?php
/*
 * This file is a part of Riskpoint Framework Software which is released under
 * MIT Open-Source license
 *
 * Riskpoint Framework Software License - MIT License
 *
 * Copyright (C) 2008 - 2017 Riskpoint London Limited
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
class Crontab {
    /**
     * Cron represents a cron command. It holds:
     * - time data
     * - command
     * - comment
     * - log files
     * - cron execution status
     */
        /**
         * @var string
         */
        protected $minute = '*';

        /**
         * @var string
         */
        protected $hour = '*';

        /**
         * @var string
         */
        protected $dayOfMonth = '*';

        /**
         * @var string
         */
        protected $month = '*';

        /**
         * @var string
         */
        protected $dayOfWeek = '*';

        /**
         * @var string
         */
        protected $command;

        /**
         * @var string
         */
        protected $logFile = null;

        /**
         * The size of the log file
         *
         * @var string
         */
        protected $logSize = null;

        /**
         * @var string
         */
        protected $errorFile = null;

        /**
         * The size of the error file
         *
         * @var string
         */
        protected $errorSize = null;

        /**
         * The last run time based on when log files have been written
         *
         * @var int
         */
        protected $lastRunTime = null;

        /**
         * The status of the cron, based on the log files
         *
         * @var string
         */
        protected $status;

        /**
         * @var string
         */
        protected $comment;

        /**
         * Parses a cron line into a Cron instance
         *
         * TODO: this deserves a serious regex
         *
         * @static
         * @param $cron string The cron line
         * @return Cron
         */
        public static function parse($cron)
        {
            $parts = \explode(' ', $cron);

            $command = \implode(' ',\array_slice($parts, 5));
            $cc = $command;
            // extract comment
            if (\strpos($command, '#')) {
                list($command, $comment) = \explode('#', $command);
                $comment = \trim($comment);
            }

            // extract error file
            if (\strpos($command, '2>')) {
                list($command, $errorFile) = \explode('2>', $command);
                $errorFile = \trim($errorFile);
            }

            // extract log file
            if (\strpos($command, '>')) {
                list($command, $logFile) = \explode('>', $command);
                $logFile = \trim($logFile);
            }

            // compute last run time, and file size
            $lastRunTime = null;
            $logSize = null;
            $errorSize = null;
            if (isset($logFile) && \file_exists($logFile)) {
                $lastRunTime = \filemtime($logFile);
                $logSize = \filesize($logFile);
            }
            if (isset($errorFile) && \file_exists($errorFile)) {
                $lastRunTime = \max($lastRunTime?:0, \filemtime($errorFile));
                $errorSize = \filesize($errorFile);
            }

            // compute status
            $status = 'error';
            if ($logSize === null && $errorSize === null) {
                $status = 'unknown';
            }
            else if ($errorSize === null || $errorSize == 0)
            {
                $status =  'success';
            }

            // create cron instance
            $cron = new self();
            $cron->setMinute($parts[0]);
            $cron->setHour($parts[1]);
            $cron->setDayOfMonth($parts[2]);
            $cron->setMonth($parts[3]);
            $cron->setDayOfWeek($parts[4]);
            $cron->setCommand($cc);
            $cron->setLastRunTime($lastRunTime);
            $cron->setLogSize($logSize);
            $cron->setErrorSize($errorSize);
            $cron->setStatus($status);
            if (isset($comment)) {
                $cron->setComment($comment);
            }
            if (isset($logFile)) {
                $cron->setLogFile($logFile);
            }
            if (isset($errorFile)) {
                $cron->setErrorFile($errorFile);
            }

            return $cron;
        }

        /**
         * @param string $command
         */
        public function setCommand($command)
        {
            $this->command = $command;
        }

        /**
         * @return string
         */
        public function getCommand()
        {
            return $this->command;
        }

        /**
         * @param string $dayOfMonth
         */
        public function setDayOfMonth($dayOfMonth)
        {
            $this->dayOfMonth = $dayOfMonth;
        }

        /**
         * @return string
         */
        public function getDayOfMonth()
        {
            return $this->dayOfMonth;
        }

        /**
         * @param string $dayOfWeek
         */
        public function setDayOfWeek($dayOfWeek)
        {
            $this->dayOfWeek = $dayOfWeek;
        }

        /**
         * @return string
         */
        public function getDayOfWeek()
        {
            return $this->dayOfWeek;
        }

        /**
         * @param string $hour
         */
        public function setHour($hour)
        {
            $this->hour = $hour;
        }

        /**
         * @return string
         */
        public function getHour()
        {
            return $this->hour;
        }

        /**
         * @param string $minute
         */
        public function setMinute($minute)
        {
            $this->minute = $minute;
        }

        /**
         * @return string
         */
        public function getMinute()
        {
            return $this->minute;
        }

        /**
         * @param string $month
         */
        public function setMonth($month)
        {
            $this->month = $month;
        }

        /**
         * @return string
         */
        public function getMonth()
        {
            return $this->month;
        }

        /**
         * @param string $comment
         */
        public function setComment($comment)
        {
            $this->comment = $comment;
        }

        /**
         * @return string
         */
        public function getComment()
        {
            return $this->comment;
        }

        /**
         * @param string $logFile
         */
        public function setLogFile($logFile)
        {
            $this->logFile = $logFile;
        }

        /**
         * @return string
         */
        public function getLogFile()
        {
            return $this->logFile;
        }

        /**
         * @param string $errorFile
         */
        public function setErrorFile($errorFile)
        {
            $this->errorFile = $errorFile;
        }

        /**
         * @return string
         */
        public function getErrorFile()
        {
            return $this->errorFile;
        }

        /**
         * @param int $lastRunTime
         */
        public function setLastRunTime($lastRunTime)
        {
            $this->lastRunTime = $lastRunTime;
        }

        /**
         * @return int
         */
        public function getLastRunTime()
        {
            return $this->lastRunTime;
        }

        /**
         * @param string $errorSize
         */
        public function setErrorSize($errorSize)
        {
            $this->errorSize = $errorSize;
        }

        /**
         * @return string
         */
        public function getErrorSize()
        {
            return $this->errorSize;
        }

        /**
         * @param string $logSize
         */
        public function setLogSize($logSize)
        {
            $this->logSize = $logSize;
        }

        /**
         * @return string
         */
        public function getLogSize()
        {
            return $this->logSize;
        }

        /**
         * @param string $status
         */
        public function setStatus($status)
        {
            $this->status = $status;
        }

        /**
         * @return string
         */
        public function getStatus()
        {
            return $this->status;
        }

        /**
         * Concats time data to get the time expression
         *
         * @return string
         */
        public function getExpression()
        {
            return \sprintf('%s %s %s %s %s', $this->minute, $this->hour, $this->dayOfMonth, $this->month, $this->dayOfWeek);
        }

        /**
         * Transforms the cron instance into a cron line
         *
         * @return string
         */
        public function __toString()
        {
            $cronLine = $this->getExpression().' '.$this->command;
            if ('' != $this->logFile) {
                $cronLine .= ' > '.$this->logFile;
            }
            if ('' != $this->errorFile) {
                $cronLine .= ' 2> '.$this->errorFile;
            }
            if ('' != $this->comment) {
                $cronLine .= ' #'.$this->comment;
            }
            return $cronLine;
        }

        public static function removeLine($key_to_delete){

            exec('crontab -l',$output);
            $lines = array_filter($output, function($line) {
                               return '' != trim($line);
                        });
            $crons = Array();
            $save  = '';
            foreach ($lines as $lineNumber => $line) {
                    if (0 !== \strpos($line, '#', 0)) {
                           $line = Crontab::parse($line);
                    }
                    $crons['l'.$lineNumber] = $line;
            }



            if($key_to_delete != 'lnew'){
                unset($crons[$key_to_delete]);
                //echo $line;
            }else{
                array_pop($crons);
            }

            foreach($crons as $command){
                if(is_a($command,'Crontab')){
                    $save .= $command->getExpression().' '.$command->getCommand().PHP_EOL;
                }
            }

            $file = tempnam(sys_get_temp_dir(), 'cron');
                    file_put_contents($file, $save);
                    exec('crontab '.$file);
        }
}
