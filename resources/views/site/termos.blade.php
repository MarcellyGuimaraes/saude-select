@extends('layouts.site')

@section('title', 'Termos de Uso | BuscarPlanos')
@section('description', 'Condições de uso do simulador e comparador de planos de saúde BuscarPlanos.')

@php($site = config('site'))

@section('content')
<article class="doc">
    <div class="wrap">
        <h1>Termos de Uso</h1>
        <p class="updated">Última atualização: 14 de novembro de 2026</p>

        <p>
            Ao usar o site e o simulador do {{ $site['name'] }}, você concorda com estes termos.
            Se não concordar, recomendamos não utilizar o serviço.
        </p>

        <h2>1. O que é o {{ $site['name'] }}</h2>
        <p>
            O {{ $site['name'] }} é um serviço de comparação e apoio à contratação de planos de saúde.
            <strong>Não somos operadora de plano de saúde</strong> e não oferecemos cobertura própria.
            Os planos apresentados pertencem às respectivas operadoras, que são as responsáveis por sua comercialização e prestação.
        </p>

        <h2>2. Natureza das informações</h2>
        <ul>
            <li>Valores, rede credenciada, carências, coberturas e disponibilidade são <strong>informativos e orientativos</strong>.</li>
            <li>Eles dependem da região, do perfil de contratação, da idade dos beneficiários e das regras de cada operadora, e podem mudar sem aviso prévio.</li>
            <li>A simulação não é uma proposta de contratação nem garante a aceitação pela operadora.</li>
            <li>Antes de contratar, leia o contrato e o material oficial da operadora.</li>
        </ul>

        <h2>3. Uso do simulador</h2>
        <p>
            Você se compromete a fornecer informações verdadeiras e a usar o serviço de forma lícita.
            É proibido tentar acessar áreas restritas, interferir no funcionamento do site ou usá-lo para fins ilegais.
        </p>

        <h2>4. Contato por WhatsApp e e-mail</h2>
        <p>
            Ao informar seu WhatsApp, você autoriza o envio do comparativo solicitado e o contato de um corretor para apoiar sua dúvida e a contratação.
            Você pode pedir para não receber mais contatos a qualquer momento.
        </p>

        <h2>5. Propriedade intelectual</h2>
        <p>
            O conteúdo e a identidade visual do {{ $site['name'] }} são protegidos por lei.
            Nomes e marcas de operadoras pertencem aos respectivos titulares e aparecem apenas para identificação.
        </p>

        <h2>6. Limitação de responsabilidade</h2>
        <p>
            Empregamos esforços razoáveis para manter as informações corretas e o serviço disponível, mas não garantimos que o site funcione sem interrupções ou erros.
            Não nos responsabilizamos por decisões tomadas apenas com base na simulação, nem por dados desatualizados fornecidos pelas operadoras.
            Isso não afasta os direitos que a lei assegura ao consumidor.
        </p>

        <h2>7. Privacidade</h2>
        <p>O tratamento dos seus dados pessoais está descrito na <a href="/privacidade">Política de Privacidade</a>.</p>

        <h2>8. Alterações</h2>
        <p>Podemos atualizar estes termos. A data da última atualização fica sempre no topo desta página.</p>

        <h2>9. Lei aplicável e contato</h2>
        <p>
            Estes termos seguem as leis do Brasil. Em caso de conflito, aplica-se o foro do domicílio do consumidor, nos termos do Código de Defesa do Consumidor.
            Dúvidas: <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>.
        </p>
    </div>
</article>
@endsection
