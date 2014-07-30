<style> 
body, table, th, td {
  font-family: "Myriad Pro", myriad-pro, Helvetica, sans-serif;
  font-size: 18px;
  line-height: 18px;
}
table { margin-top: 60px; position: relative; }
td {
  padding: 10px 15px;
  width: 350px;
}
td.key {
  font-family: "Courier New", monospace;
}
th {
  font-weight: bold;
  text-align: left;
  border-bottom: 3px solid black;
  padding-bottom: 5px;
  width: 350px;
  background-color: rgba(255,255,255,0.9);
}
th:nth-of-type(2), td.value {
  width: 500px;
}
.head-row { 
  position: fixed; 
  top: 50px;
}
h1 {
  margin: 20px 0;
}
</style>

<h1>Shib Authentication Test Page</h1>
<table cellpadding="15">
<tr class="head-row">
  <th>Header Name</th>
  <th>Header Value</th>
</tr>
<?php $_SERVER["test_wrap"] = "aiou4u9u50943u634utioadaoihfgoieh9u34hqtiquh34uthhfjkfjksdhadufhu3htui3hiuheiuahelkfkdjvbsdjkhu3huaithu4u584506734867893u609363u69386uhtuihhwph9pugu4985u6349864363498672-836u2ug4th"; ?>
<?php foreach ( $_SERVER as $key => $value ) : ?>
<?php if ( !is_array( $value ) ) : ?>
<tr>  
  <td class="key"><?php print $key; ?></td>
  <td class="value"><?php print wordwrap( $value, 50, "\n", true ); ?></td>
</tr>
<?php endif; ?>
<?php endforeach; ?>
</table>