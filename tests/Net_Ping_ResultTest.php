<?php
require_once "Net/Ping.php";
require_once 'PHPUnit/Framework.php';

class Net_Ping_ResultTest extends PHPUnit_Framework_TestCase {

    public static function data($os) {
        $result['netbsd'][] ="PING example.com (192.0.34.166): 56 data bytes";
        $result['netbsd'][] ="64 bytes from 192.0.34.166: icmp_seq=0 ttl=53 time=385.571 ms";
        $result['netbsd'][] ="64 bytes from 192.0.34.166: icmp_seq=1 ttl=53 time=173.176 ms";
        $result['netbsd'][] ="64 bytes from 192.0.34.166: icmp_seq=2 ttl=53 time=173.338 ms";
        $result['netbsd'][] ="64 bytes from 192.0.34.166: icmp_seq=3 ttl=53 time=173.915 ms";
        $result['netbsd'][] ="64 bytes from 192.0.34.166: icmp_seq=4 ttl=53 time=172.543 ms";
        $result['netbsd'][] ="";
        $result['netbsd'][] ="----example.com PING Statistics----";
        $result['netbsd'][] ="5 packets transmitted, 5 packets received, 0.0% packet loss";
        $result['netbsd'][] ="round-trip min/avg/max/stddev = 172.543/215.709/385.571/94.957 ms";

        $expect['netbsd']['min'] = 172.543;
        $expect['netbsd']['avg'] = 215.709;
        $expect['netbsd']['max'] = 385.571;
        $expect['netbsd']['stddev'] = 94.957;
        $expect['netbsd']['ttl'] = 53;
        $expect['netbsd']['bytesperreq'] = 64;
        $expect['netbsd']['received'] = 5;
        $expect['netbsd']['transmitted'] = 5;
        $expect['netbsd']['loss'] = 0;
        $expect['netbsd']['bytestotal'] = 320;
        $expect['netbsd']['targetip'] = '192.0.34.166';



        $result['freebsd'][] = "PING example.com (192.0.34.166): 56 data bytes";
        $result['freebsd'][] = "64 bytes from 192.0.34.166: icmp_seq=0 ttl=49 time=174.300 ms";
        $result['freebsd'][] = "64 bytes from 192.0.34.166: icmp_seq=1 ttl=49 time=174.174 ms";
        $result['freebsd'][] = "64 bytes from 192.0.34.166: icmp_seq=2 ttl=49 time=181.501 ms";
        $result['freebsd'][] = "64 bytes from 192.0.34.166: icmp_seq=3 ttl=49 time=184.189 ms";
        $result['freebsd'][] = "64 bytes from 192.0.34.166: icmp_seq=4 ttl=49 time=205.475 ms";
        $result['freebsd'][] = "";
        $result['freebsd'][] = "--- example.com ping statistics ---";
        $result['freebsd'][] = "5 packets transmitted, 5 packets received, 0% packet loss";
        $result['freebsd'][] = "round-trip min/avg/max/stddev = 174.174/183.928/205.475/11.472 ms";

        $expect['freebsd']['min'] = 174.174;
        $expect['freebsd']['avg'] = 183.928;
        $expect['freebsd']['max'] = 205.475;
        $expect['freebsd']['stddev'] = 11.472;
        $expect['freebsd']['ttl'] = 49;
        $expect['freebsd']['bytesperreq'] = 64;
        $expect['freebsd']['received'] = 5;
        $expect['freebsd']['transmitted'] = 5;
        $expect['freebsd']['loss'] = 0;
        $expect['freebsd']['bytestotal'] = 320;
        $expect['freebsd']['targetip'] = '192.0.34.166';



        $result['darwin'][] = "PING example.com (192.0.34.166): 56 data bytes";
        $result['darwin'][] = "64 bytes from 192.0.34.166: icmp_seq=0 ttl=49 time=255.62 ms";
        $result['darwin'][] = "64 bytes from 192.0.34.166: icmp_seq=1 ttl=49 time=277.685 ms";
        $result['darwin'][] = "64 bytes from 192.0.34.166: icmp_seq=2 ttl=49 time=342.039 ms";
        $result['darwin'][] = "64 bytes from 192.0.34.166: icmp_seq=3 ttl=49 time=290.769 ms";
        $result['darwin'][] = "";
        $result['darwin'][] = "--- example.com ping statistics ---";
        $result['darwin'][] = "4 packets transmitted, 4 packets received, 0% packet loss";
        $result['darwin'][] = "round-trip min/avg/max = 255.62/291.528/342.039 ms";

        $expect['darwin']['min'] = 255.62;
        $expect['darwin']['avg'] = 291.528;
        $expect['darwin']['max'] = 342.039;
        $expect['darwin']['stddev'] = NULL;
        $expect['darwin']['ttl'] = 49;
        $expect['darwin']['bytesperreq'] = 64;
        $expect['darwin']['received'] = 4;
        $expect['darwin']['transmitted'] = 4;
        $expect['darwin']['loss'] = 0;
        $expect['darwin']['bytestotal'] = 256;
        $expect['darwin']['targetip'] = '192.0.34.166';



        $result['aix'][] = "PING oracle.com: (148.87.9.44): 56 data bytes";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=0 ttl=245 time=891 ms";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=1 ttl=245 time=882 ms";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=2 ttl=245 time=868 ms";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=3 ttl=245 time=848 ms";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=4 ttl=245 time=863 ms";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=5 ttl=245 time=820 ms";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=6 ttl=245 time=816 ms";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=7 ttl=245 time=773 ms";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=8 ttl=245 time=855 ms";
        $result['aix'][] = "64 bytes from 148.87.9.44: icmp_seq=9 ttl=245 time=865 ms";
        $result['aix'][] = "";-
        $result['aix'][] = "---oracle.com PING Statistics----";
        $result['aix'][] = "10 packets transmitted, 10 packets received, 0% packet loss";
        $result['aix'][] = "round-trip min/avg/max = 773/848/891 ms";

        $expect['aix']['min'] = 773;
        $expect['aix']['avg'] = 848;
        $expect['aix']['max'] = 891;
        $expect['aix']['stddev'] = NULL;
        $expect['aix']['ttl'] = 245;
        $expect['aix']['bytesperreq'] = 64;
        $expect['aix']['received'] = 10;
        $expect['aix']['transmitted'] = 10;
        $expect['aix']['loss'] = 0;
        $expect['aix']['bytestotal'] = 640;
        $expect['aix']['targetip'] = '148.87.9.44';



        $result['hpux'][]="PING example.com: 64 byte packets";
        $result['hpux'][]="64 bytes from 192.0.34.166: icmp_seq=0. time=257. ms";
        $result['hpux'][]="64 bytes from 192.0.34.166: icmp_seq=1. time=280. ms";
        $result['hpux'][]="64 bytes from 192.0.34.166: icmp_seq=2. time=231. ms";
        $result['hpux'][]="";
        $result['hpux'][]="----example.com PING Statistics----";
        $result['hpux'][]="3 packets transmitted, 3 packets received, 0% packet loss";
        $result['hpux'][]="round-trip (ms)  min/avg/max = 231/256/280";

        $expect['hpux']['min'] = 231;
        $expect['hpux']['max'] = 280;
        $expect['hpux']['avg'] = 256;
        $expect['hpux']['stddev'] = NULL;
        $expect['hpux']['ttl'] = NULL;
        $expect['hpux']['bytesperreq'] = 64;
        $expect['hpux']['received'] = 3;
        $expect['hpux']['transmitted'] = 3;
        $expect['hpux']['loss'] = 0;
        $expect['hpux']['bytestotal'] = 192;
        $expect['hpux']['targetip'] = NULL;

        return array($result[$os], $expect[$os]);
    }

    public static function os() {
        //$oses = array('linux', 'freebsd', 'netbsd', 'openbsd', 'darwin', 'hpux', 'aix', 'windows');
        $oses = array(array('hpux'), array('aix'), array('darwin'), array('freebsd'), array('netbsd'));

        return $oses;
    }

    /**
     * @dataProvider os
     */
    public function testShouldParseResults($os) {
        list($result, $expect) = self::data($os);

        $ping = Net_Ping_Result::factory($result, $os);

        $this->assertFalse(PEAR::isError($ping),  "factory()");
        $this->assertEquals($expect['min'], $ping->getMin());
        $this->assertEquals($expect['avg'], $ping->getAvg());
        $this->assertEquals($expect['max'], $ping->getMax());
        $this->assertEquals($expect['stddev'], $ping->getStddev());
        $this->assertSame($os, $ping->getSystemName());
        $this->assertSame($expect['ttl'], $ping->getTTL());
        $this->assertTrue(is_array($ping->getICMPSequence()));
        $this->assertSame($expect['transmitted'], $ping->getTransmitted());
        $this->assertSame($expect['received'], $ping->getReceived());
        $this->assertSame($expect['bytesperreq'], $ping->getBytesPerRequest());
        $this->assertSame($expect['bytestotal'], $ping->getBytesTotal());
        $this->assertSame($expect['targetip'], $ping->getTargetIp());
        $this->assertSame($expect['loss'], $ping->getLoss());
    }
}
