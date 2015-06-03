<?php

/**
* Benchmark is a Singleton Class, used to measure times
* (and looping)
* counters.
* @author Pablo Alcantar
* @version 0.1
*/
class Benchmark {

    const MAINTIMER = "__main";

    private $timers        = array();
    private $stoppedTimers = array();
    private $ticks         = array();

    private static $instance = null;

    private function __construct() {
        $this->start( self::MAINTIMER );
    }

    private function __destruct() {
        $this->stop( self::MAINTIMER );
    }

    /**
    * Singleton endpoint to use class
    */
    public function instance() {
        if( !(self::$instance instanceof self) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function start( $timerID ) {
        $this->timers[$timerID] = $this->gettime();
    }

    public function elapsed( $timerID = '' ) {
        if( !$this->exists($timerID) ) {
            $timerID = self::MAINTIMER;
        }

        if( $this->stopped($timerID) ) {
            $elapsed = $this->stoppedTimers[ $timerID ];
        } else {
            $elapsed = $this->gettime() - $this->timers[ $timerID ];
        }

        return $elapsed;
    }

    public function stop( $timerID ) {
        // The main timer can't be stopped!
        if( $timerID == self::MAINTIMER ) return;

        if( !array_key_exists($timerID, $this->stoppedTimers) ) {
            $this->stoppedTimers[ $timerID ] = array();
        }

        $this->stoppedTimers[ $timerID ][] = $this->elapsed( $timerID );
        unset( $this->timers[ $timerID] );
    }

    public function tick( $timerID ) {
        if( !array_key_exists($timerID, $this->ticks) )
            $this->ticks[$timerID] = false;

        if( $this->ticks[$timerID] ) $this->stop($timerID);
        else $this->start( $timerID );

        $this->ticks[$timerID] = !$this->ticks[$timerID];
    }

    public function stopped( $timerID ) {
        return $this->exists($timerID) && array_key_exists($timerID, $this->stoppedTimers);
    }

    public function exists( $timerID ) {
        return array_key_exists($timerID, $this->timers) || array_key_exists($timerID, $this->stoppedTimers);
    }

    public function dump( $outputAs = 'txt') {
        $output = array();

        foreach($this->timers as $timerID => $time) {
            $this->stop( $timerID );
        }

        $resume = array(
            'timers'     => $this->calculateResume(),
            'total_time' => $this->elapsed()
        );

        $toLog  = strpos($outputAs, "log") !== false;
        $asJSON = strpos($outputAs, "json") !== false;
        $asTXT  = strpos($outputAs, "txt") !== false;
        $asHTML = strpos($outputAs, "html") !== false;

        if( $asJSON ) {
            $output = json_encode( $resume );

        } elseif( $asTXT || $asHTML ) {
            foreach($resume['timers'] as $timerID => $elapsed ) {
                $totalMeasured += $elapsed['total'];

                $output[] = sprintf("> %s: TOT=%0.4fs, AVG=%0.4fs TIMES=%d",
                                    $timerID, $elapsed['total'],
                                    $elapsed['average'], $elapsed['times']
                            );
            }

            $output[] = str_repeat("=", 20);
            $output[] = sprintf("Total Time: %0.4fs (measured: %0.4fs)", $resume['total_time'], $totalMeasured );

            $output = implode( $asTXT ? PHP_EOL : "<br>" , $output);

        } else {
            $output = $resume;
        }

        if( !$toLog ) return $output;
        else error_log( $output );
    }

    private function calculateResume() {
        $resume = array();

        foreach( $this->stoppedTimers as $timerID => $times ) {
            $tmp = array();

            $tmp['total']   = array_sum($times);
            $tmp['average'] = $tmp['total'] / count($times);
            $tmp['times']   = count($times);

            $resume[ $timerID ] = $tmp;
        }

        return $resume;
    }

    private function gettime() {
        return microtime(true);
    }
}

// start the main timer :D
$benchmarking = Benchmark::instance();
