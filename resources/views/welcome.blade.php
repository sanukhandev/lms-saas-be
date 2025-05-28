
<main>
    <header class="accessible-hide">
        <h1>CSS 403 Error</h1>
    </header>
    <div class="flexy-center">
        <div class="button__container">
            <div class="button" id="connect">Connect</div>
        </div>
        <div class="container" id="container">
            <svg class="computer svg-icon" id="computer" xmlns="http://www.w3.org/2000/svg" width="1024" height="1024" viewbox="0 0 1024 1024">
                <path d="M864 159.872L160 160c-17.696 0-32 14.176-32 31.872v448c0 17.696 14.304 32 32 32h704c17.696 0 32-14.304 32-32v-448c0-17.696-14.304-32-32-32zM864 640H160V191.872h704V640zm64-608H96C42.976 32 0 74.944 0 128v640c0 52.928 42.816 95.808 95.68 95.936H416v38.944l-199.744 25.952C201.984 932.384 192 945.184 192 959.872c0 17.696 14.304 32 32 32h576c17.696 0 32-14.304 32-32 0-14.688-9.984-27.488-24.256-31.072L608 902.88v-38.944h320.32c52.864-.128 95.68-43.008 95.68-95.936V128c0-53.056-43.008-96-96-96zm32 736c0 17.632-14.368 32-32 32H96c-17.664 0-32-14.368-32-32V128c0-17.664 14.336-32 32-32h832c17.632 0 32 14.336 32 32v640z"></path>
            </svg>
            <svg class="server svg-icon" id="server" xmlns="http://www.w3.org/2000/svg" width="1024" height="1024" viewbox="0 0 1024 1024">
                <path d="M512 0C296.192 0 64 65.056 64 208v608c0 142.88 232.192 208 448 208 215.776 0 448-65.12 448-208V208C960 65.056 727.744 0 512 0zm384 816c0 79.488-171.936 144-384 144-212.096 0-384-64.512-384-144V696.448C194.112 764.576 353.6 800 512 800s317.888-35.424 384-103.552V816zm0-192h-.128c0 .32.128.672.128.992C896 704 724.064 768 512 768s-384-64-384-143.008c0-.32.128-.672.128-.992H128V504.448C194.112 572.576 353.6 608 512 608s317.888-35.424 384-103.552V624zm0-192h-.128c0 .32.128.672.128.992C896 512 724.064 576 512 576s-384-64-384-143.008c0-.32.128-.672.128-.992H128V322.048C211.872 385.952 365.6 416 512 416s300.128-30.048 384-93.952V432zm-384-80c-212.096 0-384-64.512-384-144 0-79.552 171.904-144 384-144 212.064 0 384 64.448 384 144 0 79.488-171.936 144-384 144zm256 480c0-17.673 14.327-32 32-32s32 14.327 32 32c0 17.673-14.327 32-32 32s-32-14.327-32-32zm0-192c0-17.673 14.327-32 32-32s32 14.327 32 32c0 17.673-14.327 32-32 32s-32-14.327-32-32zm0-192c0-17.673 14.327-32 32-32s32 14.327 32 32c0 17.673-14.327 32-32 32s-32-14.327-32-32z"></path>
            </svg>
            <div class="response" id="response">
                <div class="item">4</div>
                <div class="item">0</div>
                <div class="item">3</div>
                <div class="sparks"></div>
            </div>
        </div>
        <div class="container__title" id="container__title">Forbidden</div>
    </div>
</main>

<style>
    @import url("https://fonts.googleapis.com/css?family=Bree+Serif");
    @import url("https://fonts.googleapis.com/css?family=Open+Sans:300");
    html, body, div, span, applet, object, iframe,
    h1, h2, h3, h4, h5, h6, p, blockquote, pre,
    a, abbr, acronym, address, big, cite, code,
    del, dfn, em, img, ins, kbd, q, s, samp,
    small, strike, strong, sub, sup, tt, var,
    b, u, i, center,
    dl, dt, dd, ol, ul, li,
    fieldset, form, label, legend,
    table, caption, tbody, tfoot, thead, tr, th, td,
    article, aside, canvas, details, embed,
    figure, figcaption, footer, header, hgroup,
    menu, nav, output, ruby, section, summary,
    time, mark, audio, video {
        margin: 0;
        padding: 0;
        border: 0;
        font: inherit;
        font-size: 100%;
        vertical-align: baseline;
    }

    html {
        line-height: 1;
    }

    ol, ul {
        list-style: none;
    }

    table {
        border-collapse: collapse;
        border-spacing: 0;
    }

    caption, th, td {
        text-align: left;
        font-weight: normal;
        vertical-align: middle;
    }

    q, blockquote {
        quotes: none;
    }
    q:before, q:after, blockquote:before, blockquote:after {
        content: "";
        content: none;
    }

    a img {
        border: none;
    }

    article, aside, details, figcaption, figure, footer, header, hgroup, main, menu, nav, section, summary {
        display: block;
    }

    html,
    body,
    main {
        height: 100%;
        width: 100%;
        box-sizing: border-box;
        -webkit-overflow-scrolling: touch;
    }

    body {
        font-size: 1em;
        line-height: 1.4rem;
        color: #ecf0f1;
        background-color: #2c3e50;
        font-family: 'Open Sans', sans-serif;
    }

    .accessible-hide {
        position: absolute;
        height: 0;
        width: 0;
        overflow: hidden;
    }

    .flexy-center {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    .svg-icon {
        width: 1em;
        height: 1em;
        fill: #ecf0f1;
    }
    .svg-icon:nth-of-type(1) {
        transform: translate(-0.55em);
    }
    .svg-icon:nth-of-type(2) {
        transform: translate(0.55em);
    }

    .button {
        padding: 0.7rem 1.4rem;
        border-radius: .2em;
        background-color: #27ae60;
        margin: 0 1.4rem;
        transition: all 0.225s ease-in-out;
        cursor: pointer;
    }
    .button__container {
        display: flex;
    }
    .button--disabled {
        pointer-events: none;
        opacity: .2;
    }

    .container {
        font-size: 3em;
        height: .45em;
        width: 5em;
        margin-top: 7rem;
        perspective: 1200px;
        position: relative;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        background-image: radial-gradient(#ecf0f1 0.015em, rgba(0, 0, 0, 0) 0.02em);
        background-size: .25em .25em;
        background-repeat: repeat-x;
        background-position: 0 bottom;
        transition: all 2.7s ease-in-out;
    }
    .container__title {
        margin-top: 1em;
        font-size: 2em;
        opacity: 0;
        transition: opacity 0.225s ease-in-out;
    }
    .container__title--anim {
        opacity: 1;
    }
    .container__anim {
        background-position: 200% bottom;
    }
    .container:before {
        content: '';
        height: 1em;
        width: calc(100% + .15em);
        position: absolute;
        left: -.075em;
        bottom: -.48em;
        box-sizing: border-box;
        padding-bottom: .25em;
        border-radius: 0 0 .25em .25em;
        border: .05em solid white;
        border-bottom-color: transparent;
        border-top-color: transparent;
        transform-style: preserve-3d;
        transform-origin: 50% 0;
        transform: rotateX(63deg);
    }
    .container__jump {
        background-image: radial-gradient(#e74c3c 0.015em, rgba(0, 0, 0, 0) 0.02em);
    }
    .container__jump .response {
        -webkit-animation: responseMove 1.125s ease-out forwards;
        animation: responseMove 1.125s ease-out forwards;
    }
    .container__jump .response .item {
        opacity: 1;
        -webkit-animation: 0.9s linear forwards;
        animation: 0.9s linear forwards;
        -webkit-animation-delay: 0.1125s;
        animation-delay: 0.1125s;
    }
    .container__jump .response .item:nth-child(1) {
        -webkit-animation-name: jump4;
        animation-name: jump4;
    }
    .container__jump .response .item:nth-child(2) {
        -webkit-animation-name: jump0;
        animation-name: jump0;
    }
    .container__jump .response .item:nth-child(3) {
        -webkit-animation-name: jump3;
        animation-name: jump3;
    }
    .container__jump .response .sparks {
        transform: scale(2);
        opacity: 0;
        background-color: #e74c3c;
    }
    .container__jump .server {
        fill: #e74c3c;
    }
    .container__jump:before {
        border-right-color: #e74c3c;
    }
    .container .svg-icon {
        position: absolute;
        bottom: 100%;
    }
    .container .svg-icon:nth-of-type(1) {
        left: 0;
    }
    .container .svg-icon:nth-of-type(2) {
        right: 0;
    }

    .response {
        position: absolute;
        right: -.5em;
        top: -1em;
    }
    .response .item {
        opacity: 0;
        line-height: 1em;
        font-family: 'Bree Serif', serif;
    }

    .sparks {
        width: 1em;
        height: 1em;
        border-radius: 1em;
        background-color: white;
        transform: scale(0);
        transition: all 0.225s ease-out;
    }

    .item {
        top: 0;
        right: 0;
        position: absolute;
        width: 0.7em;
        z-index: 2;
        text-align: center;
        transition: all 0.45s ease-out;
        display: flex;
        align-items: center;
        line-height: .87em;
        justify-content: center;
    }

    @-webkit-keyframes responseMove {
        100% {
            transform: translate(-2.5em, 0.7em) scale(1.5);
        }
    }

    @keyframes responseMove {
        100% {
            transform: translate(-2.5em, 0.7em) scale(1.5);
        }
    }
    @-webkit-keyframes jump4 {
        0% {
            transform: translateY(0) translateX(0) rotate(0);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        50% {
            transform: translateY(-3em) translateX(-0.35em) rotate(-340deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        70% {
            transform: translateY(0) translateX(-0.45em) rotate(-360deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        80% {
            transform: translateY(-1em) translateX(-0.55em) rotate(-360deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        90%,
        100% {
            transform: translateY(0) translateX(-0.65em) rotate(-360deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
            color: #e74c3c;
        }
    }
    @keyframes jump4 {
        0% {
            transform: translateY(0) translateX(0) rotate(0);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        50% {
            transform: translateY(-3em) translateX(-0.35em) rotate(-340deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        70% {
            transform: translateY(0) translateX(-0.45em) rotate(-360deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        80% {
            transform: translateY(-1em) translateX(-0.55em) rotate(-360deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        90%,
        100% {
            transform: translateY(0) translateX(-0.65em) rotate(-360deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
            color: #e74c3c;
        }
    }
    @-webkit-keyframes jump0 {
        0% {
            transform: translateY(0) translateX(0) rotate(0);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        50% {
            transform: translateY(-2em) translateX(-0.1em) rotate(-700deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        70% {
            transform: translateY(0) translateX(-0.1em) rotate(-720deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        80% {
            transform: translateY(-0.5em) translateX(-0.1em) rotate(-720deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        90%,
        100% {
            transform: translateY(0) translateX(-0.1em) rotate(-720deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
            color: #e74c3c;
        }
    }
    @keyframes jump0 {
        0% {
            transform: translateY(0) translateX(0) rotate(0);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        50% {
            transform: translateY(-2em) translateX(-0.1em) rotate(-700deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        70% {
            transform: translateY(0) translateX(-0.1em) rotate(-720deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        80% {
            transform: translateY(-0.5em) translateX(-0.1em) rotate(-720deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        90%,
        100% {
            transform: translateY(0) translateX(-0.1em) rotate(-720deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
            color: #e74c3c;
        }
    }
    @-webkit-keyframes jump3 {
        0% {
            transform: translateY(0) translateX(0) rotate(0);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        50% {
            transform: translateY(-3em) translateX(0.1em) rotate(-340deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        70% {
            transform: translateY(0) translateX(0.2em) rotate(-360deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        80% {
            transform: translateY(-1em) translateX(0.3em) rotate(-360deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        90%,
        100% {
            transform: translateY(0) translateX(0.4em) rotate(-360deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
            color: #e74c3c;
        }
    }
    @keyframes jump3 {
        0% {
            transform: translateY(0) translateX(0) rotate(0);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        50% {
            transform: translateY(-3em) translateX(0.1em) rotate(-340deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        70% {
            transform: translateY(0) translateX(0.2em) rotate(-360deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
        }
        80% {
            transform: translateY(-1em) translateX(0.3em) rotate(-360deg);
            -webkit-animation-timing-function: ease-in;
            animation-timing-function: ease-in;
        }
        90%,
        100% {
            transform: translateY(0) translateX(0.4em) rotate(-360deg);
            -webkit-animation-timing-function: ease-out;
            animation-timing-function: ease-out;
            color: #e74c3c;
        }
    }

</style>


<script>
    const computer = document.getElementById('computer');
    const response = document.getElementById('response');
    const connect = document.getElementById('connect');
    const reload = document.getElementById('reload');
    const container = document.getElementById('container');
    const containerTit = document.getElementById('container__title');

    connect.addEventListener('click', function () {
        this.classList.toggle('button--disabled');
        reload.classList.toggle('button--disabled');
        container.classList.add('container__anim');

        const onTransitionEnd = () => {
            container.classList.add('container__jump');
            container.removeEventListener('transitionend', onTransitionEnd);
            container.removeEventListener('webkitTransitionEnd', onTransitionEnd);

            const onAnimationEnd = () => {
                containerTit.classList.add('container__title--anim');
                container.removeEventListener('animationend', onAnimationEnd);
                container.removeEventListener('webkitAnimationEnd', onAnimationEnd);
            };

            container.addEventListener('animationend', onAnimationEnd);
            container.addEventListener('webkitAnimationEnd', onAnimationEnd);
        };

        container.addEventListener('transitionend', onTransitionEnd);
        container.addEventListener('webkitTransitionEnd', onTransitionEnd);
    });

    reload.addEventListener('click', function () {
        this.classList.toggle('button--disabled');
        connect.classList.toggle('button--disabled');
        container.classList.remove('container__anim', 'container__jump');
        containerTit.classList.remove('container__title--anim');
    });

</script>
