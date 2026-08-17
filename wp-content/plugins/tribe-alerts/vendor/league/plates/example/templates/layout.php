<html>
<head>
    <title><?php 
namespace Tribe\Alert_Scoped;

echo $this->e($title);
?> | <?php 
echo $this->e($company);
?></title>
</head>
<body>

<?php 
echo $this->section('content');
?>

<?php 
echo $this->section('scripts');
?>

</body>
</html><?php 
