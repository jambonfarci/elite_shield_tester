<?php

define('TESTER_PATH', "/home/freaky/elite_shield_tester/");
define('LOCK_PATH', '/tmp/shield_tester.lock');
define('OWN_URL', '/shieldtester/');

function try_lock($kind = "") {
  $fp = @fopen(LOCK_PATH . $kind, "w");
  if ($fp && @flock($fp, LOCK_EX)) {
    return $fp;
  }
}

function release_lock($fp) {
  @flock($fp, LOCK_UN);
  @fclose($fp);
}

function try_calculate() {
  $path = TESTER_PATH;
  $flags = array(
    'dps_thermal' => '-t',
    'dps_kinetic' => '-k',
    'dps_explosive' => '-e',
    'dps_absolute' => '-a',
    'effectiveness' => '-d',
    'boosters' => '-s'
  );

  $dps = 0;

  $args = array();
  foreach ($flags as $name => $flag) {
    if (isset($_GET[$name]) && is_numeric($_GET[$name])) {
      $val = abs((int)$_GET[$name]);
      if (strncmp('dps_', $name, 4) === 0) {
        $dps += $val;
      }

      if ($name == 'effectiveness') {
        $val /= 100.0;
      }

      $args[] = "$flag " . $val;
    }
  }

  $trim = 0;
  $kind = ".fast";

  if (!empty($args) && !isset($_GET['prismatics'])) {
    $args[] = "--disable-prismatic";
  }

  $boosters = max(1, min((int)@$_GET['boosters'], 8));
  // if ($boosters <= 6) {
  //   $args[] = "--disable-filter";
  // }

  if ($boosters > 6) {
    // These never seem to help
    $args[] = "--force-experimental";
    $kind = ".$boosters";
  }

  $result = "";

  if (!empty($args) && $dps > 0) {
    if ($l = try_lock($kind)) {
      $args = implode(" ", $args);
      @exec("$path/elite_shield_tester $args", $result);
      $result = implode("\n", array_slice($result, $trim));
      release_lock($l);
    } else {
      $result = "Couldn't get a lock. Try later :(";
    }
  }

  return $result;
}

function formint($name, $default = 0) {
  if (isset($_GET[$name]) && is_numeric($_GET[$name])) {
    echo (int)$_GET[$name];
  } else {
    echo $default;
  }
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">

    <title>Elite Dangerous - Shield Loadout Optimiser</title>
    <style type="text/css">
      body {
        max-width: 800px;
        margin: auto;
        font-family: sans-serif;
        background: black;
        color: #ccc;
      }

      h1 strong {
        color: #ff3b00;
        text-shadow: none;
        display: block;
      }

      h1 span {
        display: block;
        margin-left: 1em;
        color: rgb(11,176,255);
        animation: shields 8s ease-in-out infinite alternate;
      }

      @keyframes shields {
        0% {
          color: rgba(11,176,255, 1.0);
        }

        16% {
          color: rgba(11,176,255, 0.7);
        }

        32% {
          color: rgba(128,0,128, 1.0);
        }

        48% {
          color: rgba(128,0,128, 0.7);
        }

        64% {
          color: rgba(113,160,82, 1.0);
        }

        80% {
          color: rgba(113,160,82, 0.7);
        }

        100% {
          color: rgba(11,176,255, 1.0);
        }
      }

      fieldset {
        margin: 1em;
        border-color: rgb(245,59,0);
      }

      input[type="range"] {
        appearance: none;
        width: 90%;
        height: 1em;
        background: rgb(245,59,0);
        outline: none;
        opacity: 0.7;
        transition: opacity .2s;
      }

      input[type="range"]:hover {
        opacity: 1;
      }

      input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 1.2em;
        height: 1.2em;
        background: rgb(255, 170, 33);
        cursor: pointer;
      }

      input[type="range"]::-moz-range-thumb {
        width: 1.2em;
        height: 1.2em;
        background: rgb(255, 170, 33);
        cursor: pointer;
      }

      label span {
        display: inline-block;
        text-align: right;
        margin: 0 1em 0 0;
        color: rgb(255,59,0);
        font-weight: bold;
        width: 3em;
      }

      label.effectiveness span:after {
        content: "%";
      }

      label {
        display: block;
      }

      section, pre {
        max-width: 600px;
        margin: auto;
        padding: 0.2em 0.4em;
        background: rgb(28, 15, 0);
        border: 1px solid black;
      }

      pre {
        margin-bottom: 2em;
        color: white;
      }

      h1 a {
        color: black;
      }

      a {
        font-weight: bolder;
      }

      a:link {
        color: rgb(220,140,13);
        text-decoration: none;
      }

      a:visited {
        color: rgb(200,140,13);
        text-decoration: none;
      }

      a:hover, a:active {
        color: rgb(255,140,13);
        text-decoration: underline;
      }
    </style>

    <script>
      function ready(fn) {
        if (document.readyState != 'loading'){
          fn();
        } else {
          document.addEventListener('DOMContentLoaded', fn);
        }
      }

      ready(function() {
        var h1 = document.getElementById("Head");
        h1.addEventListener("click", function() {

        });

        var form = document.getElementById("Testform");
        var ranges = document.querySelectorAll("input[type='range']");

        ranges.forEach(function(item, i) {
          var el = document.createElement("span");
          el.innerHTML = item.value;
          item.addEventListener("input", function() {
            el.innerHTML = item.value;
          });
          form.addEventListener("reset", function() {
            setTimeout(function() { el.innerHTML = item.value; }, 1);
          });
          item.parentNode.insertBefore(el, item);
        });
      });
    </script>

    <meta name="description" content="Elite Dangerous - Find the best shield and shield booster engineering for your ship">
    <meta name="author" content="Thomas Hurst">
  </head>
  <body>
    <h1 id="Head"><a href="<?php echo OWN_URL ?>"><strong>Elite Dangerous</strong> <span>Shield Loadout Optimiser</span></a></h1>

    <main>
      <section>
        <p>
          Determine the best shield engineering for your <a href="https://www.elitedangerous.com/">Elite Dangerous</a>
          warship through fancy algorithms and a smidgen of computational brute force.
        </p>

        <p>
          This website is a frontend to a <a href="https://www.rust-lang.org/">Rust</a> rework of
          <a href="https://www.youtube.com/channel/UCg3QI9rHzPgvR7KTKSCtPHg">Down to Earth Astronomy</a>'s
          <a href="https://github.com/DownToEarthAstronomy/D2EA_Shield_tester">shield tester</a>,
          as shown in
          <a href="https://www.youtube.com/watch?v=87DMWz8IeEE">this video</a>.
          It is neither affiliated with nor endorsed by D2EA.  Source code is available
          <a href="https://github.com/Freaky/elite_shield_tester">on Github</a>.
        </p>

        <p>
          Special thanks to <a href="https://github.com/ntt">Jamie "Entity" van den Berge</a> for the algorithm that makes this so fast.
        </p>
      </section>

      <form id="Testform" action="<?php echo OWN_URL ?>" method="get">
        <fieldset><legend>Attacker Damage Per Second</legend>
          <label>Explosive<br>
            <input type="range" name="dps_explosive" min="0" value="<?php formint('dps_explosive') ?>" max="200">
          </label><br>
          <label>Thermal<br>
            <input type="range" name="dps_thermal" min="0" value="<?php formint('dps_thermal') ?>" max="200">
          </label><br>
          <label>Kinetic<br>
            <input type="range" name="dps_kinetic" min="0" value="<?php formint('dps_kinetic') ?>" max="200">
          </label><br>
          <label>Absolute<br>
            <input type="range" name="dps_absolute" min="0" value="<?php formint('dps_absolute') ?>" max="200">
          </label><br><br>
          <label class="effectiveness">Effectiveness<br>
            <input type="range" name="effectiveness" min="1" value="<?php formint('effectiveness', 50) ?>" max="100">
          </label>
        </fieldset>

        <fieldset><legend>Defender</legend>
          <label>Shield Boosters<br>
            <input type="range" name="boosters" value="<?php formint('boosters', 2) ?>" min="1" max="8">
          </label><br>

          <label>Allow Prismatic Shields
            <input type="checkbox" name="prismatics" <?php if (isset($_GET['prismatics'])) { echo 'checked="checked"'; } ?>>
          </label>
        </fieldset>

        <fieldset><legend>Neat Buttons</legend>
          <input type="submit" value="Calculate">
          <input type="reset" value="Reset">
        </fieldset>
      </form>
    </main>

<?php
    $result = try_calculate();
    if (!empty($result)) {
?>
      <pre>
<?php echo htmlentities($result); ?>
      </pre>
<?php
    }
?>

  </body>
</html>
