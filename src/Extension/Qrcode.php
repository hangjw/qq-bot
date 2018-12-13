<?php


namespace QqBot\Extension;


use PHPZxing\PHPZxingDecoder;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use PHPQRCode\QRcode as QrCodeConsole;


class Qrcode
{

    private static function initQrcodeStyle(OutputInterface $output)
    {
        $style = new OutputFormatterStyle('black', 'black', ['bold']);
        $output->getFormatter()->setStyle('blackc', $style);
        $style = new OutputFormatterStyle('white', 'white', ['bold']);
        $output->getFormatter()->setStyle('whitec', $style);
    }




    public function showQr($res)
    {
        $file_name = __DIR__ . '/qr.png';
        if ($res) {
            file_put_contents($file_name, $res);
            $this->show((new PHPZxingDecoder())->decode($file_name)->getImageValue());
            @unlink($file_name);
            echo "请打开二维码扫码登录" . PHP_EOL;
        } else {
            die('获取登录二维码失败');
        }
    }

    private function isWin()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    private function show($text)
    {
        $output = new ConsoleOutput();
        $this->initQrcodeStyle($output);

        $pxMap[0] = $this->isWin() ? '<whitec>mm</whitec>' : '<whitec>  </whitec>';
        $pxMap[1] = '<blackc>  </blackc>';

        $text = QrCodeConsole::text($text);

        $length = strlen($text[0]);

        $output->write("\n");
        foreach ($text as $line) {
            $output->write($pxMap[0]);
            for ($i = 0; $i < $length; $i++) {
                $type = substr($line, $i, 1);
                $output->write($pxMap[$type]);
            }
            $output->writeln($pxMap[0]);
        }
    }
}