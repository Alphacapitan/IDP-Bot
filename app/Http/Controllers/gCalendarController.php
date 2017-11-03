<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use \Spatie\GoogleCalendar\Event;

use Carbon\Carbon;

class gCalendarController extends Controller 
{
    public function getTodayEvents() {
        /**
         * Carica gli eventi di oggi
         * @return collection
         */
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today(), Carbon::today()->addHour(23));
        $results = $this->formatEvent($events);
        echo $results;
    }

    public function getTomorrowEvents() {
        /**
         * Carica gli eventi di domani
         * @return collection
         */
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::tomorrow(), Carbon::tomorrow()->endOfDay());
        $results = $this->formatEvent($events);
        echo $results;
    }

    public function getCurrentWeekEvents() {
        /**
         * Carica gli eventi della settimana attuale
         * @return collection
         */
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today()->startOfWeek(), Carbon::today()->startOfWeek()->addDay(5));
        $results = $this->formatEvent($events);
        echo $results;
    }

    public function getNextWeekEvents() {
        /**
         * Carica gli eventi della settimana prossima
         * @return collection
         */
        // ricava gli eventi a partire da data di inizio e di fine
        $events = Event::get(Carbon::today()->startOfWeek()->next(Carbon::MONDAY), Carbon::today()->startOfWeek()->next(Carbon::MONDAY)->addDay(5));
        $results = $this->formatEvent($events);
        echo $results;
    }

    // 
    // formatazione risposta
    // 
    public function formatEvent($collection) {
        /**
         * Converte gli eventi in messaggio
         * @return string
         */

        // controlla se esistono eventi nella collection
        if ($collection->isNotEmpty()) {

            // $collection->dd();
            
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
                    $message = $message . "🗓" . __("day." . $event["day"]) . "\n\n";
                }

                // compila il messaggio
                $message = $message . "📒 $event[title] - $event[desc]" . "\n" . "⏱ Dalle $event[inizio] Alle $event[fine]" . "\n\n";
            }

            // ritorno il messaggio
            return $message;
        } else {
            // caso senza eventi
            return "Non ci sono lezioni ✋";
        }
    }
}