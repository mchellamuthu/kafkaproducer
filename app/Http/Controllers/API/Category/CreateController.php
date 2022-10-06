<?php

namespace App\Http\Controllers\API\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class CreateController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'name' => 'required|string'
        ]);
        $category = \App\Models\Category::create(['name' => $request->name]);
        $message = new Message(
            body: [
                'action' => 'created',
                'resource' => get_class($category),
                'old_values' => '',
                'new_values' => $category->getRawOriginal()
            ],
        );
        Kafka::publishOn('auditlogs')->withSasl(new \Junges\Kafka\Config\Sasl(
            password: config('kafka.secret'),
            username: config('kafka.key'),
            mechanisms: 'PLAIN',
            securityProtocol: 'SASL_SSL',
        ))
        ->withMessage($message)->send();
        return response()->json(['msg' => 'created']);
    }
}
