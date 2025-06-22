<?php ?>

<style>
    /* Load the fonts from Google */
    @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Poppins:wght@400;600;700&display=swap');
    
    * {
        padding: 0;
        margin: 0;
        box-sizing: border-box;
    }
    
    html {
        font-size: 80%;
        font-family: 'Open Sans', sans-serif;
    }
    
    h1, h2, h3, h4, h5, h6 {
        font-family: 'Poppins', sans-serif;
        font-weight: bold;
    }
    
    /* ################################################################################################# */

    .flex{
        display: flex;
    }

    .flex-raw{
        flex: 1;
        min-width: 200px;
    }

    .fullflex{
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .flex-auto{
        flex: 1;
        min-width: 200px;
    }

    .flex-wrap{
        display: flex;
        flex-wrap: wrap;
    }

    .border-p{
        border: .1rem solid orange;
    }

    .justify-beteen{
        justify-content: space-between;
    }

    .gap-1{
        gap: 1rem;
    }

    .size-1{
        font-size: 1.5rem;
    }

    .margin-top-1{
        margin: 0 1rem 0 0;
    }

    .padding-1{
        padding: 1rem;
    }

    .padding-2{
        padding: 2rem;
    }

    li{
        list-style: none;
    }

    ul a{
        text-decoration: none;
        color: #fff;
    }

    .bold{
        font-weight: 700;
    }
</style>