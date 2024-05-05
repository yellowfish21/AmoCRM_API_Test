<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ufee\Amo\Oauthapi;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|ascii',
            'birthdate' => 'required|date',
            'phone' => 'required|ascii|min:9',
            'city' => 'required|ascii',
            'form' => 'required|ascii|in:Test'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages()->first(), 418);
        }

        $order = new Order();
        $contact_info = $validator->safe()->except('form');
        $order->hook = json_encode($contact_info);
        $order->form = $validator->safe(['form'])['form'];
        $order->save();

        $exitCode = \Artisan::call('amo:issue-token');
        if ($exitCode <= 0) {
            return response()->json(['Cannot store order in amoCRM'], 400);
        }

        $amo = Oauthapi::setInstance(config('amocrm.account'));

        // Create contact
        $contact = $amo->contacts()->create();
        $contact->name = $contact_info['name'];
        $contact->cf('Телефон')->setValue($contact_info['phone']);
        $amo->contacts()->add($contact);

        // Create lead from contact
        $lead = $contact->createLead();
        $lead->name = 'Amoapi v7';
        $lead->attachTag($contact_info['city']);
        $lead->save();

        return response()->json(['Order has been created'], 201);
    }
}
