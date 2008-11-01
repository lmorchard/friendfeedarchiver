<?php 
/**
 * Quotes from This Island Earth, stolen from:
 * http://members.tripod.com/~prism64801/thisislandearthsounds.htm
 * http://www.imdb.com/title/tt0047577/quotes
 */
ob_start(); 
?>
    Iterociter incorporating planetary generator. Iterociter with voltarator. With astroscope.
    Complete line of iterociter parts, incorporating greater advances than hitherto known in the field of electronics.
    You have successfully accomplished your task, Dr. Meacham.
    You've assembled an Interocitor, a feat of which few men are capable.
    Why should a communication device be equipped with a destructive ray?
    The Transformer is not the only answer.
    Only instead of a suntan, you get your brain cells rearranged. 
    As you can see, their spacecraft are actually guiding the meteors against us.
    The intense heat is turning Metaluna into a radioactive sun.
    I feel like a new toothbrush.
    You boys like to call this the pushbutton age. It isn't, not yet. 
    Not until we can team up atomic energy with electronics. 
    Then we'll have the horses as well as the cart. 
    Our ionization layer must be maintained until our relocation is effected. 
    We hope to live in harmony with the citizens of your Earth.
    I kind of expected Neptune. 
    There isn't any reason around here.
    It's only Neutron. We call him that because he's so positive. 
<?php
$content = ob_get_contents();
ob_end_clean();
$lines = split("\n", $content);
$quote = $lines[rand(1, count($lines)-2)]; 
?>
<div id="flavorquote">
    <p class="quote"><?php out::H($quote) ?></p>
</div>
