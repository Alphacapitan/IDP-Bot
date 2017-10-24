<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
//use App\Conversations\ExampleConversation;

use \Spatie\GoogleCalendar\Event;
use Carbon\Carbon;


class BotManController extends Controller
{
    /**
     * Place your BotMan logic here.
     */
    public function handle()
    {
        $botman = app('botman');

        $botman->hears('^.*(\Bene).*$', function ($bot) {
            $bot->types();
            $bot->reply('Ma non benissimo 😎!');
        });

        $botman->hears('domani', function ($bot) {
            $results = $this->getTomorrowEvents();
            $bot->reply($results);
        });

        $botman->fallback(function(Botman $bot) {
            $bot->reply("Grandissima la ragazza che la ride... YAAA 😘");
        });

        $botman->listen();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tinker()
    {
        return view('tinker');
    }

    /**
     * Loaded through routes/botman.php
     * @param  BotMan $bot
     */
    public function startConversation(BotMan $bot)
    {
        $bot->startConversation(new ExampleConversation());
    }

    public function getTomorrowEvents() {
        return "string";
    }

}

