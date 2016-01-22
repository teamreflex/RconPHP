<?php

namespace Reflex\Rcon;

use Reflex\Rcon\Exceptions\NotAuthenticatedException;
use Reflex\Rcon\Exceptions\RconAuthException;
use Reflex\Rcon\Exceptions\RconConnectException;

class Rcon
{
    const SERVERDATA_AUTH = 3;
    const SERVERDATA_EXECCOMMAND = 2;

    /**
     * The Rcon server IP.
     *
     * @var string
     */
    protected $ip;

    /**
     * The Rcon server port.
     *
     * @var integer
     */
    protected $port;

    /**
     * The Rcon password.
     *
     * @var string 
     */
    protected $password;

    /**
     * The Rcon socket.
     *
     * @var resource 
     */
    protected $socket;

    /**
     * The current packet ID.
     *
     * @var integer 
     */
    protected $packetID;

    /**
     * Is the client connected to the server?
     *
     * @var boolean 
     */
    public $connected = false;

    /**
     * Constructs the Rcon client.
     *
     * @param string $ip
     * @param int $port 
     * @param string $password 
     * @return void 
     */
    public function __construct($ip, $port, $password)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->password = $password;
    }

    /**
     * Connects and authenticates with the CS:GO server.
     *
     * @return boolean 
     */
    public function connect()
    {
        $socket = stream_socket_client("tcp://{$this->ip}:{$this->port}", $errno, $errstr);

        stream_set_timeout($socket, 2, 500);

        if (!$socket) {
            throw new RconConnectException("Error while connecting to the Rcon server: {$errstr}");
        }

        $this->socket = $socket;

        $this->write(self::SERVERDATA_AUTH, $this->password);

        $read = $this->read();
        if ($read[1]['ID'] == -1) {
            throw new RconAuthException('Authentication to the Rcon server failed.');
        }

        $this->connected = true;

        return true;
    }

    /**
     * Sets the timeout on the socket in seconds.
     *
     * @param integer $timeout 
     * @return boolean 
     */
    public function setTimeout($timeout = 2)
    {
        stream_set_timeout($this->stream, $timeout);

        return true;
    }

    /**
     * Executes a command on the server.
     *
     * @param string $command 
     * @return string 
     */
    public function exec($command)
    {
        if (!$this->connected) {
            throw new NotAuthenticatedException('Client has not connected to the Rcon server.');
        }

        $this->write(self::SERVERDATA_EXECCOMMAND, $command);

        return $this->read()[0]['S1'];
    }

    /**
     * Writes to the socket.
     *
     * @param integer $type
     * @param string $s1
     * @param string $s2 
     * @return integer 
     */
    public function write($type, $s1 = '', $s2 = '')
    {
        $id = $this->packetID++;

        $data  = pack('VV', $id, $type);
        $data .= $s1.chr(0).$s2.chr(0);
        $data  = pack('V', strlen($data)).$data;

        fwrite($this->socket, $data, strlen($data));

        return $id;
    }

    /**
     * Reads from the socket.
     *
     * @return array 
     */
    public function read()
    {
        $rarray = [];
        $count = 0;

        while ($data = fread($this->socket, 4)) {
            $data = unpack('V1Size', $data);

            if ($data['Size'] > 4096) {
                $packet = '';
                for ($i = 0; $i < 8; $i++) {
                    $packet .= "\x00";
                }
                $packet .= fread($this->socket, 4096);
            } else {
                $packet = fread($this->socket, $data['Size']);
            }

            $rarray[] = unpack('V1ID/V1Response/a*S1/a*S2', $packet);
        }

        return $rarray;
    }

    /**
     * Closes the socket.
     *
     * @return boolean 
     */
    public function close()
    {
        if (!$this->connected) {
            return false;
        }

        $this->connected = false;
        fclose($this->socket);

        return true;
    }

    /**
     * Alias for close().
     *
     * @return boolean 
     */
    public function disconnect()
    {
        return $this->close();
    }
}
