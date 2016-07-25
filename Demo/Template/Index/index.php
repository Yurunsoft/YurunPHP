<?php
echo $this->get('msg');
?>
<div>
	<select runat="server" dataset="<php>array(20,50,100)</php>" select_text="100" first_item_text="全部" first_item_value=""></select>
</div>
<div>
	<radio runat="server" name="IsApprove" text="同意" value="1"></radio>
	<radio runat="server" name="IsApprove" text="不同意" value="0"></radio>
</div>
<div>
	<radiogroup runat="server" name="sex" dataset="<php>array(
            array('text'=>'男'),
            array('text'=>'女'),
        )</php>" checked_value="男"></radiogroup>
</div>
<div>
	<checkbox runat="server" name="c2" id="c2" text="标题" checked="0"></checkbox>
</div>
<div>
	<checkboxgroup name="g1" runat="server" text="标题" data_func="test/getTest" text_field="name" value_field="id" checked_field="checked"></checkboxgroup>
</div>
<div>
	<table runat="server" dataset="<php>
		array(
			array('id'=>11,'name'=>'aaa'),
			array('id'=>22,'name'=>'bbb'),
			array('id'=>33,'name'=>'ccc'),
		)
	</php>" id="tb" auto_head="0">
		<row runat="server">
			<col runat="server">序号</col>
			<col runat="server">ID</col>
			<col runat="server">姓名</col>
		</row>
		<datarow runat="server">
		    <datacol runat="server" type="index"></datacol>
		    <datacol runat="server" field="id"></datacol>
		    <datacol runat="server">姓名<%={name}%></datacol>
	    </datarow>
	</table>
</div>
<div>
	<textbox runat="server" text="abc"/>
</div>
<div>
	<pagebar formid="form1" runat="server" total_records="101" curr_page="5" page_show="10"></pagebar>
</div>
<p>PHP版本：<?php echo PHP_VERSION;?></p>
<p>运行耗时：<?php echo microtime(true)-YURUN_START;?></p>