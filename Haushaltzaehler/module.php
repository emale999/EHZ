<?php

//declare(strict_types = 1);

class Haushaltzaehler extends IPSModule
{
    public function __construct($InstanceID)
    {
        //Never delete this line!
        parent::__construct($InstanceID);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyInteger('Gateway', 0);
        $this->RegisterPropertyInteger('Update', 1);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        switch ($this->ReadPropertyInteger('Gateway')) {
            case 0: //ClientSocket
                $this->ForceParent('{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}');
                break;
            case 1: //SerialPort
                $this->ForceParent('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
                break;
            case 2: //UDPSocket
                $this->ForceParent('{82347F20-F541-41E1-AC5B-A636FD3AE2D8}');
                break;
            case 3: //Virtualport
                $this->ForceParent('{6179ED6A-FC31-413C-BB8E-1204150CF376}');
                break;
        }
    }

    //Add this Polyfill for IP-Symcon 4.4 and older
    protected function SetValue($Ident, $Value)
    {
        if (IPS_GetKernelVersion() >= 5) {
            parent::SetValue($Ident, $Value);
        } else {
            SetValue($this->GetIDForIdent($Ident), $Value);
        }
    }

    /*
    //Add this Polyfill for IP-Symcon 4.4 and older
    protected function GetValue($Ident)
    {
        if (IPS_GetKernelVersion() >= 5) {
            parent::GetValue($Ident);
        } else {
            GetValue($this->GetIDForIdent($Ident));
        }
    }*/

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $bufferdata = $this->GetBuffer('Buffer');
        $data = $bufferdata.utf8_decode($data->Buffer);

        if (strpos($data, "\x1B\x1B\x1B\x1B\x01\x01\x01\x01") === false) {
            $this->SetBuffer('Buffer', $data);

            return;
        } else {
            $packet = $data;
        }

        $packets = explode("\x1B\x1B\x1B\x1B\x01\x01\x01\x01", $data);

        $tail = array_pop($packets);
        if (strpos($tail, "\x1B\x1B\x1B\x1B") === false) {
            $this->SetBuffer('Buffer', $tail);
        } else {
            $this->SetBuffer('Buffer', '');
        }

        if (strripos($packet, "\x1B\x1B\x1B\x1B") !== false) {
            $packet = stristr($packet, "\x01\x01\x01\x63", true);
            $this->SendDebug('Receive Paket', $packet, 1);
            $this->setInformation($packet);
            $this->setSml($packet);
        }
    }

    private function setInformation($dataSml)
    {
        if (strpos($dataSml, "\x81\x81\xC7\x82\x03\xFF") !== false) {
            $herstellerID = stristr($dataSml, "\x81\x81\xC7\x82\x03\xFF");
            $this->SetVariableString('Hersteller', 'Hersteller', '', substr($herstellerID, 11, 3));
        }

        if (strpos($dataSml, "\x01\x00\x00\x00\x09\xFF") !== false) {
            $serverID = stristr($dataSml, "\x01\x00\x00\x00\x09\xFF");
            $this->SetVariableString('Server', 'ServerID', '', $this->Str2Hex(substr($serverID, 11, 10)));
        }

        if (strpos($dataSml, "\x01\x00\x60\x01\x00\xFF") !== false) {

            // DZG noch unklar! Ident irgendwas setzt erst mal den Hersteller.
            $ID = stristr($dataSml, "\x01\x00\x60\x01\x00\xFF");
            $this->SetVariableString('Hersteller', 'Hersteller', '', substr($ID, 19, 3));
        }

        if (strpos($dataSml, "\x81\x81\xC7\x82\x05\xFF") !== false) {
            $keySplit = stristr($dataSml, "\x81\x81\xC7\x82\x05\xFF");
            $key = $this->KeyData(substr($keySplit, 12));
            $this->SetVariableString('PublicKey', 'PublicKey', '', $key);
        }
    }

    private function setSml($dataSml)
    {
        $sml = explode("\x01\x77\x07", $dataSml);

        unset($sml[0]);
        unset($sml[1]);
        $sml = array_values($sml);

        $scaler = '';

        foreach ($sml as $data) {
            if (strpos($data, "\x62\x1B\x52") !== false) {
                $powerData = stristr($data, chr(0x1B));
                $indexPower = stristr($data, chr(0x1B), true);

                if (strpos($powerData, chr(0x52)) !== false) {
                    switch (substr($powerData, 2, 1)) {
                        case chr(0xFE):
                            $scaler = 100;
                            break;
                        case chr(0x00):
                            $scaler = 1;
                            break;
                        case chr(0xFF):
                            $scaler = 10;
                            break;
                        default:
                            $this->SendDebug('Data Error ', $powerData, 1);
                    }

                    $index = $this->Str2Hex(substr($indexPower, 2, 3));
                    $ident = hexdec($index);
                    $split = substr($powerData, 4, -2);
                    $valueData = hexdec($this->Str2Hex(substr($powerData, 4)));

                    if ($split != "\xFF\xFF" && is_numeric($valueData)) {
                        $value = $valueData / $scaler;
                    } else {
                        $value = hexdec($this->Str2Hex($split)) - hexdec($this->Str2Hex(substr($powerData, -2)));
                        $value = $value / 10;
                    }
                    $this->SetVariableFloat($ident, $index, '~Watt.14490', $value);
                }
            }

            if (strpos($data, "\x62\x1E\x52") !== false) {
                $energieData = stristr($data, chr(0x1E));
                $indexEnergie = stristr($data, chr(0x1E), true);

                if (strpos($energieData, chr(0x52)) !== false) {
                    switch (substr($energieData, 2, 1)) {
                        case chr(0xFE):
                            $scaler = 100;
                            break;
                        case chr(0xFC):
                            $scaler = 10000000;
                            break;
                        case chr(0xFF):
                            $scaler = 10000;
                            break;
                        case chr(0x03):
                            $scaler = 1;
                            break;
                        case chr(0x00):
                            $scaler = 1;
                            break;
                        default:
                            $this->SendDebug('Data Error ', $energieData, 1);
                    }

                    $index = $this->Str2Hex(substr($indexEnergie, 2, 3));
                    $ident = hexdec($index);
                    $valueData = hexdec($this->Str2Hex(substr($energieData, 4)));

                    if ($valueData != 0 && is_numeric($valueData)) {
                        $value = $valueData / $scaler;
                        $this->SetVariableFloat($ident, $index, '~Electricity', $value);
                    }
                }
            }
        }
    }

    private function SetVariableFloat($ident, $name, $profile, $value)
    {
        $this->RegisterVariableFloat($ident, $name, $profile);
        $varUpdated = IPS_GetVariableCompatibility($this->GetIDForIdent($ident));
        if (microtime(true) - $varUpdated['VariableUpdated'] >= $this->ReadPropertyInteger('Update')) {
            $this->SetValue($ident, number_format($value, 2, ',', ''));
        }
    }

    private function SetVariableString($ident, $name, $profile, $value)
    {
        $this->RegisterVariableString($ident, $name, $profile);
        if ($this->GetValue($ident) == '') {
            $this->SetValue($ident, $value);
        }
    }

    private function Str2Hex($daten)
    {
        $hex = '';
        for ($i = 0; $i < strlen($daten); $i++) {
            $hex .= sprintf('%02X ', ord($daten[$i]));
        }

        return $hex;
    }

    private function KeyData($data)
    {
        $key = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $key .= sprintf('%02X', ord($data[$i]));
        }
        $key = str_split($key, 4);
        $key = implode($key, ' ');

        return $key;
    }
}
