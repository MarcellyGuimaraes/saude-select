@extends('layouts.site')

@section('title', 'Política de Privacidade | BuscarPlanos')
@section('description', 'Como o BuscarPlanos coleta, usa e protege seus dados pessoais, em conformidade com a Lei Geral de Proteção de Dados (LGPD).')

@php($site = config('site'))

@section('content')
<article class="doc">
    <div class="wrap">
        <h1>Política de Privacidade</h1>
        <p class="updated">Última atualização: 14 de novembro de 2026</p>

        <p>
            Esta política explica como o {{ $site['name'] }} trata os dados pessoais de quem usa o simulador de planos de saúde,
            de acordo com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018, a LGPD).
        </p>

        <h2>1. Quem é o controlador dos dados</h2>
        <p>
            O controlador é <strong>{{ $site['legal_name'] ?: $site['name'] }}</strong>@if ($site['cnpj']), CNPJ {{ $site['cnpj'] }}@endif.
            Para qualquer assunto sobre seus dados, fale conosco em
            <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>.
        </p>

        <h2>2. Quais dados coletamos</h2>
        <ul>
            <li><strong>Dados informados por você no simulador:</strong> nome, número de WhatsApp, cidade e estado, tipo de contratação (CPF, PME ou adesão), profissão (quando o perfil exige), quantidade de pessoas por faixa de idade e os planos que você selecionou.</li>
            <li><strong>Dados de navegação:</strong> páginas e etapas visitadas, origem do acesso (como parâmetros de campanha), tipo de dispositivo e navegador, e identificadores de cookies.</li>
            <li><strong>Localização aproximada:</strong> estimada pelo endereço IP ou, se você permitir, pelo GPS do aparelho, apenas para indicar os planos da sua região.</li>
        </ul>

        <h2>3. Para que usamos seus dados</h2>
        <ul>
            <li>gerar a simulação e o comparativo de planos que você pediu;</li>
            <li>enviar o comparativo em PDF por WhatsApp e, quando necessário, por e-mail;</li>
            <li>permitir que um corretor entre em contato para tirar dúvidas e apoiar a contratação;</li>
            <li>medir e melhorar o desempenho do site e de nossas campanhas, por meio de dados de navegação;</li>
            <li>cumprir obrigações legais e proteger direitos em caso de necessidade.</li>
        </ul>

        <h2>4. Bases legais</h2>
        <p>
            Tratamos seus dados com base na execução de procedimentos preliminares a pedido seu (a cotação), no seu consentimento
            (por exemplo, para o contato por WhatsApp e para cookies de medição), no legítimo interesse de melhorar nossos serviços
            e no cumprimento de obrigações legais, conforme o art. 7º da LGPD.
        </p>

        <h2>5. Com quem compartilhamos</h2>
        <p>Compartilhamos dados apenas quando necessário para a finalidade acima, com:</p>
        <ul>
            <li>corretores e parceiros comerciais que atendem sua solicitação de cotação;</li>
            <li>plataformas de simulação de planos usadas para gerar os valores e a rede credenciada;</li>
            <li>fornecedores de tecnologia, como serviços de envio de mensagens por WhatsApp, hospedagem e e-mail;</li>
            <li>ferramentas de medição e publicidade, como Google Tag Manager e plataformas de anúncios, que podem receber dados de navegação.</li>
        </ul>
        <p>Não vendemos seus dados pessoais.</p>

        <h2>6. Cookies e tecnologias semelhantes</h2>
        <p>
            Usamos cookies e identificadores para manter o funcionamento do simulador, entender como o site é usado e avaliar o desempenho de anúncios.
            Você pode bloquear ou apagar cookies nas configurações do seu navegador, mas partes do site podem deixar de funcionar corretamente.
        </p>

        <h2>7. Por quanto tempo guardamos</h2>
        <p>
            Mantemos os dados pelo tempo necessário para cumprir as finalidades descritas nesta política ou para atender a obrigações legais.
            Depois disso, eles são excluídos ou anonimizados.
        </p>

        <h2>8. Seus direitos</h2>
        <p>Nos termos do art. 18 da LGPD, você pode solicitar, a qualquer momento:</p>
        <ul>
            <li>confirmação de que tratamos seus dados e acesso a eles;</li>
            <li>correção de dados incompletos, inexatos ou desatualizados;</li>
            <li>anonimização, bloqueio ou eliminação de dados desnecessários ou tratados em desconformidade;</li>
            <li>portabilidade dos dados;</li>
            <li>eliminação dos dados tratados com base no consentimento;</li>
            <li>informações sobre com quem compartilhamos seus dados;</li>
            <li>revogação do consentimento.</li>
        </ul>
        <p>Para exercer esses direitos, escreva para <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>.</p>

        <h2>9. Segurança</h2>
        <p>
            Adotamos medidas técnicas e organizacionais para proteger seus dados, como conexão segura (HTTPS) e controle de acesso.
            Nenhum sistema é totalmente imune a riscos, mas trabalhamos para reduzi-los.
        </p>

        <h2>10. Transferência internacional</h2>
        <p>
            Algumas ferramentas que usamos, como as do Google, podem armazenar dados em servidores fora do Brasil.
            Nesses casos, buscamos fornecedores que adotem salvaguardas compatíveis com a LGPD.
        </p>

        <h2>11. Alterações desta política</h2>
        <p>Podemos atualizar esta política. A data da última atualização fica sempre no topo desta página.</p>

        <h2>12. Contato</h2>
        <p>
            Dúvidas sobre privacidade? Fale com a gente em <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>.
            Você também pode reclamar à Autoridade Nacional de Proteção de Dados (ANPD).
        </p>
    </div>
</article>
@endsection
