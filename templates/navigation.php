<section class="accounts-navigation darkbg">

	<div class="container">

		<div class="wrap">

			<?php foreach($data as $section) : ?>

				<a href="<?php echo $section['href']; ?>" class="button-underline"><?php echo $section['text']; ?></a>

			<?php endforeach; ?>

		</div>

	</div>

</section>