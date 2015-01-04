<div class="form">
    <h2>Connect with Facebook</h2>

    <?php if ($this->Facebook->user()): ?>
        <?php debug($this->Facebook->user()); ?>

        <?php echo $this->Html->link(__('Access Token'), array('action' => 'token')); ?>
    <?php else: ?>
        <?php echo $this->Html->link(__('Connect with facebook'), $this->get('loginUrl')); ?>
        <p><?php echo h($this->get('loginUrl')); ?></p>

    <?php endif; ?>

    <?php debug($_SESSION); ?>
</div>


