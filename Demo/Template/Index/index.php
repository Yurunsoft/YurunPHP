<?php
echo $this->get('msg');
?>
<p>PHP版本：<?php echo PHP_VERSION;?></p>
<p>运行耗时：<?php echo microtime(true)-YURUN_START;?></p>