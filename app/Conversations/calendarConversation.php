<?php

namespace App\Conversations;

use Illuminate\Foundation\Inspiring;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;

use Spatie\GoogleCalendar\Event;
use Carbon\Carbon;

class calendarConversation extends Conversation
{

    /**
     * First question
     */
    public function whatTime()
    {
        $question = Question::create("Di quale orario hai bisogno?")
            ->fallback('Abilitato alla richiesta')
            ->callbackId('orario')
            ->addButtons([
                Button::create('Oggi')->value('today'),
                Button::create('Domani')->value('tomorrow'),
                Button::create('Questa settimana')->value('thisWeek'),
                Button::create('Prossima settimana')->value('nextWeek'),
            ]);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->getEvents($answer->getValue());
            }
        });
    }

    public function run()
    {
        // This will be called immediately
        $this->whatTime();
    }

    /**
     * Carica gli eventi di oggi
     * @return collection
     */
    public function getEvents($time) 
    {
        if ($time == "today") {
            $events = $this->getTodayEvents();
        } else if ($time == "tomorrow") {
            $events = $this->getTomorrowEvents();
        } else if ($time == "thisWeek") {
            $events = $this->getCurrentWeekEvents();
        } else if ($time == "nextWeek") {
            $events = $this->getNextWeekEvents();
        };

        $results = $this->formatEvent($events);
        $this->say($results);
    }

    /**
     * Carica gli eventi di oggi
     * @return collection
     */
    public function getTodayEvents() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today(), Carbon::today()->endOfDay());
        return $events;
    }

    /**
     * Carica gli eventi di domani
     * @return collection
     */
    public function getTomorrowEvents() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::tomorrow(), Carbon::tomorrow()->endOfDay());
        return $events;
    }

    /**
     * Carica gli eventi di domani
     * @return collection
     */
    public function getCurrentWeekEvents() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today()->startOfWeek(), Carbon::today()->startOfWeek()->addDay(5));
        return $events;
    }

    /**
     * Carica gli eventi di domani
     * @return collection
     */
    public function getNextWeekEvents() 
    {
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today()->startOfWeek()->next(Carbon::MONDAY), Carbon::today()->startOfWeek()->next(Carbon::MONDAY)->addDay(5));
        return $events;
    }

    /**
     * Converte gli eventi in messaggio
     * @return string
     */ 
    public function formatEvent($collection) 
    {
        // controlla se esistono eventi nella collection
        if ($collection->isNotEmpty()) {
            
            // converte la collection in json
            $collection->toJSON();
            // crea la collection di dati base --> verranno poi aggiunti i dati degli eventi
            $baseCollection = collect();

            foreach ($collection as $key => $item) {
                // data di inizio
                $dt_Init = Carbon::parse($item->googleEvent->start->dateTime);
                // data di fine
                $dt_End = Carbon::parse($item->googleEvent->end->dateTime);
                
                // crea l'array con i dati per i singoli eventi
                $baseCollection->push(
                    array(
                        "date" => Carbon::createFromDate($dt_Init->year, $dt_Init->month, $dt_Init->day)->format('d / m'),
                        "day" => Carbon::createFromDate($dt_Init->year, $dt_Init->month, $dt_Init->day)->format('l'),
                        "title" => $item->googleEvent->summary,
                        "desc" => $item->googleEvent->description,
                        "inizio" => Carbon::createFromTime($dt_Init->hour, $dt_Init->minute)->format('H:i'),
                        "fine" => Carbon::createFromTime($dt_End->hour, $dt_End->minute)->format('H:i'),
                    )
                );
            }
            // crea il messaggio
            $message = "";

            // inizio scrittura messaggio
            foreach ($baseCollection as $key => $event) {
                
                // controlla se il giorno del evento attuale nel ciclo è uguale o diverso dall'evento prima
                if ($key == 0 || $event["day"] != $baseCollection[$key - 1]["day"]) {
                    // in sia il primo evento o diverso aggiunge il giorno dell'evento
                    $message = $message . "🗓" . __("day." . $event["day"]) . " - " . $event["date"] . "\n\n";
                }

                // compila il messaggio
                $message = $message . "📒 $event[title] - $event[desc]" . "\n" . "⏱ Dalle $event[inizio] Alle $event[fine]" . "\n\n";
            }

            // ritorno il messaggio
            return $message;

        } else {
            // ritorno il messaggio che non ci sono lezioni
            return "Non ci sono lezioni ✋";
        }
    }
}