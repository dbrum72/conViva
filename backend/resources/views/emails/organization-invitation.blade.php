<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Convite para o conViva
    </title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background: #f5f5f3;
        font-family: Arial, Helvetica, sans-serif;
        color: #263028;
    ">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="
            width: 100%;
            background: #f5f5f3;
        ">
        <tr>
            <td align="center" style="
                    padding: 40px 16px;
                ">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                    style="
                        width: 100%;
                        max-width: 600px;
                        background: #ffffff;
                        border-radius: 12px;
                        overflow: hidden;
                    ">
                    <tr>
                        <td style="
                                padding: 32px;
                            ">
                            <h1
                                style="
                                    margin: 0 0 24px;
                                    font-size: 24px;
                                    line-height: 1.3;
                                    color: #263028;
                                ">
                                Você foi convidado para o conViva
                            </h1>

                            <p
                                style="
                                    margin: 0 0 16px;
                                    font-size: 16px;
                                    line-height: 1.6;
                                ">
                                Você recebeu um convite para integrar a organização
                                <strong>
                                    {{ $invitation->organization->name }}
                                </strong>.
                            </p>

                            <p
                                style="
                                    margin: 0 0 16px;
                                    font-size: 16px;
                                    line-height: 1.6;
                                ">
                                Sua função inicial será:
                                <strong>
                                    {{ $invitation->role }}
                                </strong>.
                            </p>

                            @if ($invitation->inviter)
                                <p
                                    style="
                                        margin: 0 0 24px;
                                        font-size: 16px;
                                        line-height: 1.6;
                                    ">
                                    Convite enviado por
                                    <strong>
                                        {{ $invitation->inviter->name }}
                                    </strong>.
                                </p>
                            @endif

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                style="
                                    margin: 28px 0;
                                ">
                                <tr>
                                    <td
                                        style="
                                            border-radius: 8px;
                                            background: #445c3c;
                                        ">
                                        <a href="{{ $acceptanceUrl }}"
                                            style="
                                                display: inline-block;
                                                padding: 14px 24px;
                                                color: #ffffff;
                                                text-decoration: none;
                                                font-size: 16px;
                                                font-weight: 700;
                                            ">
                                            Aceitar convite
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p
                                style="
                                    margin: 0 0 12px;
                                    font-size: 14px;
                                    line-height: 1.6;
                                    color: #677268;
                                ">
                                Este convite expira em
                                {{ $invitation->expires_at->format('d/m/Y \à\s H:i') }}.
                            </p>

                            <p
                                style="
                                    margin: 0;
                                    font-size: 13px;
                                    line-height: 1.6;
                                    color: #7a847c;
                                ">
                                Se você não esperava este convite, ignore esta mensagem.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
