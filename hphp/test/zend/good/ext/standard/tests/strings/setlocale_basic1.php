<?hh
/* Prototype  : string setlocale (int $category , string $locale [,string $..] )
              : string setlocale(int $category , array $locale);
 * Description: Sets locale information.Returns the new current locale ,
 *              or FALSE if locale functionality is not implemented in this platform.
 * Source code: ext/standard/string.c
*/

/* test setlocale by specifying a specific locale as input */

/* Prototype  : array list_system_locales( void )
   Description: To get the currently installed locle in this platform
   Arguments  : Nil
   Returns    : set of locale as array
*/
function list_system_locales() {
  // start the buffering of next command to internal output buffer
  ob_start();

  // run the command 'locale -a' to fetch all locales available in the system
  system('locale -a');

  // get the contents from the internal output buffer
  $all_locales = ob_get_contents();

  // fflush and end the output buffering to internal output buffer
  ob_end_clean();

  $system_locales = explode("\n", $all_locales);

  // return all the locale found in the system
  return $system_locales;
}

/* Collect existing system locales and set one among them,
   Check the currency settings in the new locale  */
<<__EntryPoint>> function main(): void {
echo "*** Testing setlocale() : basic functionality - set to a specific locale ***\n";
//set of locales to be used
$common_locales = array(
  "english_US"=> "en_US.utf8",
  "english_AU" => "en_AU.utf8",
  "korean_KR" => "ko_KR.utf8",
  "Chinese_zh" => "zh_CN.utf8",
  "germen_DE" => "de_DE.utf8",
  "spanish_es" => "es_EC.utf8",
  "french_FR" => "fr_FR.utf8",
  "japanees_JP" => "ja_JP.utf8",
  "greek_GR" => "el_GR.utf8",
  "dutch_NL" => "nl_NL.utf8"
);

//set of currency symbol according to above list of locales
$currency_symbol = array(
  "en_US.utf8" => "USD",
  "en_AU.utf8" => "AUD",
  "ko_KR.utf8" => "KRW",
  "zh_CN.utf8" => "CNY",
  "de_DE.utf8" => "EUR",
  "es_EC.utf8" => "USD",
  "fr_FR.utf8" => "EUR",
  "ja_JP.utf8" => "JPY",
  "el_GR.utf8" => "EUR",
  "nl_NL.utf8" =>"EUR"
);

// gather all the locales installed in the system
$all_system_locales = list_system_locales();

// set the system locale to a locale, choose the right locale by
// finding a common locale in commonly used locale stored in
// $common_locales & locales that are available in the system, stored
// in $all_system_locales.
echo "Setting system locale(LC_ALL) to ";
foreach($common_locales as $value) {
  // check if a commonly used locale is installed in the system
  if(in_array($value, $all_system_locales)){
    echo "$value\n"; // print, this is found
    // set the found locale as current locale
    var_dump(setlocale(LC_ALL, $value ));
    // stop here
    break;
  }
  else{
    // continue to check if next commonly locale is installed in the system
    continue;
  }
}

// check that new locale setting is effective
// use localeconv() to get the details of currently set locale
$locale_info = localeconv();

//checking currency settings in the new locale to see if the setlocale() was effective
$new_currency = trim($locale_info['int_curr_symbol']);
echo "Checking currency settings in the new locale, expected: ".$currency_symbol[$value].", Found: ".$new_currency."\n";
echo "Test ";
if(trim($currency_symbol[$value]) == $new_currency){
  echo "PASSED.";
} else {
  echo "FAILED.";
}

echo "\nDone\n";
}
