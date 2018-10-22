<?php

namespace MatthiasMullie\ApiOauth\Controllers\ResetPassword;

class Get extends Base
{
    /**
     * @inheritdoc
     */
    protected function get(array $args, array $get): array
    {
        $html = $this->getFormHtml();
        return ['body' => $html];
    }

    /**
     * @return string
     */
    public function getFormHtml(): string
    {
        return '<html>
<body>
<form method="POST">
    <label for="password">New password</label>
    <input id="password" type="password" name="password" required>
    <input type="submit" value="Submit">
</form>
</body>
</html>';
    }
}
