<html>
	<head>
		<title>Upload Storage test</title>
	</head>
	<body>
		<?= Form::open(NULL, array('enctype' => 'multipart/form-data')) ?>
		
			<?= Form::label('file') ?>
			<? if (Upload::stored('file')) : ?>
			
				Already stored (<?= Arr::get(Upload::get('file'), 'name') ?>)
			
			<? else : ?>
			
				<?= Form::file('file', array('id' => 'file')) ?>
			
			<? endif ?>
			
			<hr />
			
			<?= Form::checkbox('validate',1,FALSE,array('id' => 'validate')) ?>
			<?= Form::label('validate','Validate and remove storage') ?>
		
			<hr />
		
			<?= Form::button('submit','Submit') ?>
		
		<?= Form::close() ?>
	</body>
</html>
