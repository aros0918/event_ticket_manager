<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode; 

class EmailPreviewController extends Controller
{
    public function showOrderEmail()
    {
        // Test data

        // Generate QR code

        // Prepare view data
          // $qrCodes = [];
  
          // $qrCodes['simple']        = QrCode::size(150)->generate('https://minhazulmin.github.io/');
          // $qrCodes['changeColor']   = QrCode::size(150)->color(255, 0, 0)->generate('https://minhazulmin.github.io/');
          // $qrCodes['changeBgColor'] = QrCode::size(150)->backgroundColor(255, 0, 0)->generate('https://minhazulmin.github.io/');
          // $qrCodes['styleDot']      = QrCode::size(150)->style('dot')->generate('https://minhazulmin.github.io/');
          // $qrCodes['styleSquare']   = QrCode::size(150)->style('square')->generate('https://minhazulmin.github.io/');
          // $qrCodes['styleRound']    = QrCode::size(150)->style('round')->generate('https://minhazulmin.github.io/');
  
          $qrCodes = [
            'simple' => 'data:image/png;base64,' . base64_encode(QrCode::format('png')->size(150)->generate('https://minhazulmin.github.io/')),
            'changeColor' => 'data:image/png;base64,' . base64_encode(QrCode::format('png')->size(150)->color(255, 0, 0)->generate('https://minhazulmin.github.io/')),
            'changeBgColor' => 'data:image/png;base64,' . base64_encode(QrCode::format('png')->size(150)->backgroundColor(255, 0, 0)->generate('https://minhazulmin.github.io/')),
            'styleDot' => 'data:image/png;base64,' . base64_encode(QrCode::format('png')->size(150)->style('dot')->generate('https://minhazulmin.github.io/')),
            'styleSquare' => 'data:image/png;base64,' . base64_encode(QrCode::format('png')->size(150)->style('square')->generate('https://minhazulmin.github.io/')),
            'styleRound' => 'data:image/png;base64,' . base64_encode(QrCode::format('png')->size(150)->style('round')->generate('https://minhazulmin.github.io/')),
        ];
        // Render the email template
        return view('emails.order', $qrCodes);
    }
}
