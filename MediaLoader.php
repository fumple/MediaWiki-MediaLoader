<?php
//Error "constant"
function error(String $msg, Parser $parser, PPFrame $frame){
	$syntax = $parser->recursiveTagParse("[[Special:MediaLoaderSyntax|Correct syntax]]", $frame);
	return "MediaLoader Error: $msg $syntax";
}

//Get elapsed time
function elapsed($time){
	$diff = microtime(true) - $time;
	return "<div style='display:none'>MediaLoader Performance log<br>Time elapsed: $diff</div>";
}

//Remove quotes for security reasons
function removeQuotes($str){
	$str2 = str_replace("\"", " ", $str);
	$str2 = str_replace("'", " ", $str2);
	return $str2;
}
class MediaLoaderPHP {
	//Add js + css + broken function
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$output = $out->getOutput();
		$output->addModules( 'ext.mediaLoader' );
		$output->addModuleStyles('ext.mediaLoader');/*
		$html = $output->getHTML();
		$dom = new DOMDocument;
		libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_clear_errors();
		$node = $dom->getElementById('file');
		$node2 = $dom->nextSibling;
		$pagetitle = $output->getPageTitle();
		if($node != null && $node2 != null && substr($pagetitle, 0, 5) == "File:"){
			$node2child = $node2->firstChild;
			if($node2child == null){
				return;
			}
			$allowedtypes = array(
				'ogg' => 'audio',
				'mp3' => 'audio',
				'wav' => 'audio',
				'mp4' => 'video'
			);
			$child = $node->firstChild;
			if($child == null){return;}
			$href = $child->getAttribute("href");
			if($href == null){return;}
			$hrefsplit = explode(".", $href);
			$fileext = end($hrefsplit);
			if(!isset($allowedtypes[$fileext])){
				return;
			}
			$filetype = $allowedtypes[$fileext];
			$node->textContent = "";
			if($filetype == "audio"){
				$node2child->textContent = "<div class='MediaLoaderOuter'>
					<audio class='MediaLoader MediaLoaderAudio' src='$href' controls></audio>
					</div>";
				$readyhtml = $dom->saveHTML($dom->getElementsByTagName("body")[0]->textContent);
				$output->clearHTML();
				$output->addHTML(substr($readyhtml, 6, strlen($readyhtml)-13));
			}
			if($filetype == "video"){
				$node2child->textContent = "<div class='MediaLoaderOuter'>
					<video class='MediaLoader MediaLoaderVideo' src='$href' controls width='500px'></video>
					</div>";
				$readyhtml = $dom->saveHTML($dom->getElementsByTagName("body")[0]->textContent);
				$output->clearHTML();
				$output->addHTML(substr($readyhtml, 6, strlen($readyhtml)-13));
			}
		}*/
	}
	
	// Register any render callbacks with the parser
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'media', [ self::class, 'renderTagMedia' ] );
		$parser->setHook( 'mediagroup', [ self::class, 'renderTagMediaGroup' ] );
	}
	//Render <media>
	public static function renderTagMedia( $input, array $args, Parser $parser, PPFrame $frame ) {
		//Initial values
		$starttime = microtime(true); // Calculate time elapsed
		global $wgMediaLoaderLoadText;
		global $wgMediaLoaderUnloadText;
		$allowedtypes = array(
			'ogg' => 'audio',
			'mp3' => 'audio',
			'wav' => 'audio',
			'png' => 'image',
			'jpg' => 'image',
			'gif' => 'image',
			'bmp' => 'image',
			'mp4' => 'video'
		);

		//Check if the name provided is a file
		if(substr($input, 0, 5) === "File:"){
			//Try to get the file url
			$filename = substr($input, 5);
			$mediaoutput = $parser->recursiveTagParse( "[[Media:".$filename."]]", $frame );
			$dom = new DOMDocument;
			libxml_use_internal_errors(true);
			$dom->loadHTML($mediaoutput);
			libxml_clear_errors();
			$node = $dom->getElementsByTagName('a');
			if(count($node) == 0){
				return error("Not a file!", $parser, $frame);
			}
			$node = $node[0];
			if(!$node->hasAttribute( 'href' )){
				return error("Incorrect file!", $parser, $frame);
			}
			$href = $node->getAttribute( 'href' );
			$class = $node->getAttribute( 'class' );
			if($class == "new"){
				//New file
				$parsed = $parser->recursiveTagParse("[[File:".$filename."]]", $frame);
				return error("File $parsed does not exist!", $parser, $frame);
			}

			//Check extension
			$hrefsplit = explode(".", $href);
			$fileext = end($hrefsplit);
			if(!isset($allowedtypes[$fileext])){
				return error("Unsupported file type <b>$fileext</b>!", $parser, $frame);
			}
			$filetype = $allowedtypes[$fileext];

			//Handle all args
			if(!isset($args["load"])){
				$load = false;
			}
			else{
				$load = true;
			}

			if(!isset($args["group"])){
				$group = "";
			}
			else{
				$group = $args["group"];
				if(substr($group, -1) != '/'){
					$group = $group.'/';
				}
			}
			$group = htmlentities($group);

			if(!isset($args["name"])){
				$name = $filename;
			}
			else{
				$name = $args["name"];
			}
			$name = htmlentities($name);

			if(!isset($args["volume"])){
				$volume = 1;
			}
			else{
				$volume = $args["volume"];
			}
			$volume = htmlentities($volume);

			if(!isset($args["loop"])){
				$loop = "";
			}
			else{
				$loop = "loop";
			}

			if(!isset($args["autoplay"])){
				$autoplay = "";
			}
			else{
				$autoplay = "autoplay";
			}

			if(!isset($args["width"])){
				$width = "";
			}
			else{
				$width = "width='".htmlentities($args["width"])."'";
			}

			if(!isset($args["height"])){
				$height = "";
			}
			else{
				$height = "height='".htmlentities($args["height"])."'";
			}

			if(!isset($args["args"])){
				$uargs = "";
			}
			else{
				$uargs = str_replace(" ", "|", $args["args"]);
				$uargs = removeQuotes($uargs);
			}

			//Get direct file link
			$mediaoutput = $parser->recursiveTagParse( "[[:$input]]", $frame );
			$dom = new DOMDocument;
			libxml_use_internal_errors(true);
			$dom->loadHTML($mediaoutput);
			libxml_clear_errors();
			$node = $dom->getElementsByTagName('a');
			if(count($node) == 0){
				return error("Not a file!", $parser, $frame);
			}
			$node = $node[0];
			if(!$node->hasAttribute( 'href' )){
				return error("Incorrect file!", $parser, $frame);
			}
			$li = $node->getAttribute( 'href' );

			//Get load/unload text for both files
			$lt = explode('{file}', $wgMediaLoaderLoadText);
			$ut = explode('{file}', $wgMediaLoaderUnloadText);
			if(count($lt) != 1){
				$lt = str_replace("{file}", $name, $wgMediaLoaderLoadText);
			}
			else{
				$lt = $wgMediaLoaderLoadText.$name;
			}
			if(count($ut) != 1){
				$ut = str_replace("{file}", $name, $wgMediaLoaderUnloadText);
			}
			else{
				$ut = $wgMediaLoaderUnloadText.$name;
			}

			//Return ready file based on type
			if($filetype == "image"){
				$readyimage = $parser->recursiveTagParse("[[File:$filename|$uargs]]");
				if(!$load){
					return "<div class='MediaLoaderOuter MediaLoaderImage'>
					<div class='MediaLoaderInner MediaLoaderImage'>$readyimage</div>
					</div>".elapsed($starttime);
				}
				else{
					return "<div class='MediaLoaderOuter MediaLoaderImage MediaLoad'>
					<div class='MediaLoader MediaLoaderInner MediaLoad MediaLoaderID' file='$li' type='image' group='$group' image='$readyimage' loadtext='$lt' unloadtext='$ut'>
					<div class='MediaLoader MediaLoaderInner MediaLinkText'>$mediaoutput</div>
					</div></div>".elapsed($starttime);
				}
			}
			if($filetype == "audio"){
				if(!$load){
					return "<div class='MediaLoaderOuter MediaLoaderAudio'>
					<audio class='MediaLoader MediaLoaderAudio MediaLoaderToSet' $autoplay src='$href' volume='$volume' controls $loop></audio>
					</div>".elapsed($starttime);
				}
				else{
					return "<div class='MediaLoaderOuter MediaLoaderAudio MediaLoad'>
					<div class='MediaLoader MediaLoaderInner MediaLoad MediaLoaderID' file='$li' $autoplay type='audio' group='$group' src='$href' volume='$volume' $loop loadtext='$lt' unloadtext='$ut'>
					<div class='MediaLoader MediaLoaderInner MediaLinkText'>$mediaoutput</div>
					</div></div>".elapsed($starttime);
				}
			}
			if($filetype == "video"){
				if(!$load){
					return "<div class='MediaLoaderOuter MediaLoaderVideo'>
					<video class='MediaLoader MediaLoaderVideo MediaLoaderToSet' $autoplay src='$href' volume='$volume' controls $loop $width $height></video>
					</div>".elapsed($starttime);
				}
				else{
					return "<div class='MediaLoaderOuter MediaLoaderVideo MediaLoad'>
					<div class='MediaLoader MediaLoaderInner MediaLoad MediaLoaderID' file='$li' $autoplay $width $height type='video' group='$group' src='$href' volume='$volume' $loop loadtext='$lt' unloadtext='$ut'>
					<div class='MediaLoader MediaLoaderInner MediaLinkText'>$mediaoutput</div>
					</div></div>".elapsed($starttime);
				}
			}
		}
		else{
			return error("Not a file!", $parser, $frame);
		}
	}
	//Render <mediagroup>
	public static function renderTagMediaGroup( $input, array $args, Parser $parser, PPFrame $frame ) {
		//Vars
		$starttime = microtime(true); // Calculate time elapsed
		global $wgMediaLoaderLoadAllText;
		global $wgMediaLoaderUnloadAllText;
		global $wgMediaLoaderLoadAllGroupText;
		global $wgMediaLoaderUnloadAllGroupText;

		//Check if the person added a subgroup
		if(strpos($input, '/') !== false){
			$ia = explode('/', $input);
			$i = end($ia);
		}
		else{
			$i = $input;
		}

		//Check for a name
		if(isset($args["name"])){
			$name = $args["name"];
		}
		else{
			$name = $i;
		}

		//Check for a group
		if($input != ""){
			//Get the texts for Loading and replace the {group}
			$lt = explode('{group}', $wgMediaLoaderLoadAllGroupText);
			$ut = explode('{group}', $wgMediaLoaderUnloadAllGroupText);
			if(count($lt) != 1){
				$lt = str_replace("{group}", $name, $wgMediaLoaderLoadAllGroupText);
			}
			else{
				$lt = $wgMediaLoaderLoadAllText.$name;
			}
			if(count($ut) != 1){
				$ut = str_replace("{group}", $name, $wgMediaLoaderUnloadAllGroupText);
			}
			else{
				$ut = $wgMediaLoaderUnloadAllText.$name;
			}
		}
		else{
			//Set the vars
			$lt = $wgMediaLoaderLoadAllText;
			$ut = $wgMediaLoaderUnloadAllText;
		}
		return "<div class='MediaLoaderOuter MediaLoaderGroupSet MediaLoader'>
		<div class='MediaLoaderInner MediaLoader MediaLoaderGroupSet' group='$input' loadtext='$lt' unloadtext='$ut'></div>
		</div>";
	}
}

?>