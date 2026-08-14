<?php
class WorldEventsProvider {
    public function eventsForYears(array $years): array {
        $data=[
            ['year'=>1969,'date'=>'1969-07-20','title'=>'Moon landing','description'=>'Apollo 11 becomes the first crewed mission to land on the Moon.'],
            ['year'=>1989,'date'=>'1989-11-09','title'=>'Berlin Wall falls','description'=>'The fall of the Berlin Wall becomes a defining moment in the end of the Cold War.'],
            ['year'=>1991,'date'=>'1991-08-06','title'=>'World Wide Web opened','description'=>'The World Wide Web becomes publicly available.'],
            ['year'=>2001,'date'=>'2001-09-11','title'=>'September 11 attacks','description'=>'A major historical event that reshaped global politics and society.'],
            ['year'=>2008,'date'=>'2008-09-15','title'=>'Global financial crisis','description'=>'The global financial crisis causes major economic disruption.'],
            ['year'=>2020,'date'=>'2020-03-11','title'=>'COVID-19 declared a pandemic','description'=>'The World Health Organization declares COVID-19 a pandemic.'],
            ['year'=>2022,'date'=>'2022-02-24','title'=>'Russia invades Ukraine','description'=>'A major geopolitical event with global humanitarian and economic impact.'],
            ['year'=>2024,'date'=>'2024-07-26','title'=>'Paris Summer Olympics','description'=>'The Summer Olympic Games open in Paris.']
        ]; $wanted=array_fill_keys(array_map('intval',$years),true); return array_values(array_filter($data,fn($e)=>isset($wanted[$e['year']])));
    }
}
?>
