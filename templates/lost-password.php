<section class="accounts-section accounts-lost-password row">

	<div class="container">

		<div class="wrap">

			<div class="panel">

				<h4 class="title">Recover password</h4>

				<div class="text">

					<form action="/account" method="post"  name="account-creation-form" class="account-creation-form">

						<div class="field field-email">

							<label>Email</label>

							<input type="email" name="account[email]" placeholder="Email" value="" required>

						</div>

						<div class="field field-submit">

							<input type="hidden" name="accounts_action" value="accounts_lost_password">
							<?php wp_nonce_field( 'accounts' ); ?>

							<button type="submit" name="submit">
								<span class="text">Recover Password</span>
								<span class="icon icon-right-open"></span>
							</button>

						</div>

					</form>

				</div>

			</div>

		</div><!-- //End New Account -->

	</div>

</section>
