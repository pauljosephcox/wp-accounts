<section class="accounts-section accounts-section-activate row darkbg">

	<div class="container">

		<div class="wrap">

			<div class="panel">

				<?php $user = get_account_from_activation_key($_GET['key']); ?>
				<h4 class="title">Activate <?php echo $user->data->user_email; ?></h4>

				<div class="text">

					<form action="/account" method="post" name="account-activate-form">

						<div class="field field-password">

							<label>Password <span class="req">*</span></label>

							<input type="password" id="password" name="account[password]" placeholder="Password" value="" required>

						</div>

						<div class="field field-submit">

							<input type="hidden" name="accounts_action" value="accounts_activate_account">
							<input type="hidden" name="account[key]" value="<?php echo $_GET['key']; ?>">
							<input type="hidden" name="account[email]" placeholder="Email" value="<?php echo $user->data->user_email; ?>">
							<?php wp_nonce_field( 'accounts' ); ?>

							<button type="submit" name="submit">
								<span class="text">Activate Account</span>
								<span class="icon icon-right-open"></span>
							</button>

						</div>

					</form>

				</div>

			</div>

		</div>

	</div>

</section>
