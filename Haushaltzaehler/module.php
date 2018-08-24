<?php


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
            $this->setSml($packet);
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
            if (strpos($data, "\x1B\x52") !== false) {
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

                    if ($split != "\xFF\xFF") {
                        $value = hexdec($this->Str2Hex(substr($powerData, 4))) / $scaler;
                    } else {
                        $value = hexdec($this->Str2Hex($split)) - hexdec($this->Str2Hex(substr($powerData, -2)));
                        $value = $value / 10;
                    }
                    $this->SetVariableFloat($ident, $index, '~Watt.14490', $value);
                }
            }

            if (strpos($data, "\x1E\x52") !== false) {
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

                    $value = hexdec($this->Str2Hex(substr($energieData, 4))) / $scaler;

                    if ($value != 0) {
                        $this->SetVariableFloat($ident, $index, '~Electricity', $value);
                    }
                }
            }
        }
    }

    private function SetVariableFloat($ident, $name, $profile, $value)
    {
        $this->RegisterVariableFloat($ident, $name, $profile);
        $this->SetValue($ident, number_format($value, 0, ',', ''));
    }

    private function Str2Hex($daten)
    {
        $hex = '';
        for ($i = 0; $i < strlen($daten); $i++) {
            $hex .= sprintf('%02X ', ord($daten[$i]));
        }

        return $hex;
    }

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
            $this->setSml($packet);
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
            if (strpos($data, "\x1B\x52") !== false) {
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

                    if ($split != "\xFF\xFF") {
                        $value = hexdec($this->Str2Hex(substr($powerData, 4))) / $scaler;
                    } else {
                        $value = hexdec($this->Str2Hex($split)) - hexdec($this->Str2Hex(substr($powerData, -2)));
                        $value = $value / 10;
                    }
                    $this->SetVariableFloat($ident, $index, '~Watt.14490', $value);
                }
            }

            if (strpos($data, "\x1E\x52") !== false) {
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

                    $value = hexdec($this->Str2Hex(substr($energieData, 4))) / $scaler;

                    if ($value != 0) {
                        $this->SetVariableFloat($ident, $index, '~Electricity', $value);
                    }
                }
            }
        }
    }

    private function SetVariableFloat($ident, $name, $profile, $value)
    {
        $this->RegisterVariableFloat($ident, $name, $profile);
        $this->SetValue($ident, number_format($value, 0, ',', ''));
    }

    private function Str2Hex($daten)
    {
        $hex = '';
        for ($i = 0; $i < strlen($daten); $i++) {
            $hex .= sprintf('%02X ', ord($daten[$i]));
        }

        return $hex;
    }
}
