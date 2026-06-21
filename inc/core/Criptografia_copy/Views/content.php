<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto mt-5">
        <div class="mb-5">
            <h2><i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _ec($config['name']) ?></h2>
            <p><?php _e($config['desc']) ?></p>
        </div>
    </div>
</div>
<div class="container  mb-5 card p-25 b-r-10 text-gray-700">
  <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"> Escreva abaixo o texto que deseja Criptografar e clique no botão com o seu objetivo específico</h5>
    <form id="formulario" >
       <div class="card b-r-10 mb-5">
       <div class="card-body p-10">
      <textarea type="text" id="campo" name="name" placeholder="Escreva seu Texto Aqui" class="form-control"></textarea><br>
      <div id="select">
        <input type="submit" value="E-mail" id="MI" class="btn btn-info">
        <input type="submit" value="SMS e Marcas" id="TX" class="btn btn-info">
        <input type="submit" value="Anuncios e Sites " id="BI" class="btn btn-info">
      </div><br>

      <script type="text/javascript">
        result=document.getElementById("cript");
        var form=document.getElementById("formulario"),
        campo=document.getElementById("campo"),
        bi=document.getElementById("BI"),
        tx=document.getElementById("TX"),
        mi=document.getElementById("MI");
        mi.addEventListener("click",function(b){var a="&#8238;",a=a+campo.value.split("").reverse().join("");
        b.preventDefault(),document.getElementById("cript").innerHTML=a}),tx.addEventListener("click",function(e){for(var a="",c=campo.value.split(""),b=0;c.length!=b;b+=1){var d=c;switch(d[b]){
          case"a":a+="\u0430";
            break;
          case"c":a+="\u0441";
            break;
          case"e":a+="\u0435";
            break;
          case"i":a+="\u0456";
            break;
          case"j":a+="\u0458";
            break;
          case"o":a+="\u043E";
            break;
          case"p":a+="\u0440";
            break;
          case"s":a+="\u0455";
            break;
          case"x":a+="\u0445";
            break;
          case"y":a+="\u0443";
            break;
          case"A":a+="\u0410";
            break;
          case"B":a+="\u0412";
            break;
          case"C":a+="\u0421";
            break;
          case"E":a+="\u0415";
            break;
          case"H":a+="\u041D";
            break;
          case"I":a+="I";
            break;
          case"K":a+="\u039A";
            break;
          case"M":a+="\u041C";
            break;
          case"N":a+="\u039D";
            break;
          case"O":a+="\u041E";
            break;
          case"P":a+="\u0420";
            break;
          case"S":a+="\u0405";
            break;
          case"T":a+="\u0422";
            break;
          case"X":a+="\u0425";
            break;
          case"Y":a+="\u03A5";
            break;
          case"Z":a+="\u0396";
            break;
          case" ":a+="";
            default:a+=d[b]}}e.preventDefault(),document.getElementById("cript").innerHTML=a}),bi.addEventListener("click",function(e){for(var a="",c=campo.value.split(""),b=0;c.length!=b;b+=1){var d=c;switch(a+="\u200B",d[b]){case"a":a+="\u0430";break;case"c":a+="\u0441";break;case"e":a+="\u0435";break;case"i":a+="\u0456";break;case"j":a+="\u0458";break;case"o":a+="\u043E";break;case"p":a+="\u0440";break;case"s":a+="\u0455";break;case"x":a+="\u0445";break;case"y":a+="\u0443";break;case"A":a+="\u0410";break;case"B":a+="\u0412";break;case"C":a+="\u0421";break;case"E":a+="\u0415";break;case"H":a+="\u041D";break;case"I":a+="I";break;case"K":a+="\u039A";break;case"M":a+="\u041C";break;case"N":a+="\u039D";break;case"O":a+="\u041E";break;case"P":a+="\u0420";break;case"S":a+="\u0405";break;case"T":a+="\u0422";break;case"X":a+="\u0425";break;case"Y":a+="\u03A5";break;case"Z":a+="\u0396";break;case" ":a+="";default:a+=d[b]}}e.preventDefault(),document.getElementById("cript").innerHTML=a})
      </script>
    </form>
    <h5 class="border-bottom m-b-30 p-b-20 text-dark text-uppercase"> Abaixo foi gerado seu texto criptografado. É só copiar e colar nos seus anúncios, páginas, e-mails, etc...</h5>
    
    <textarea id="cript" class="form-control"></textarea><br>
    <button type="button" onclick="copiarHtml()" class="btn btn-success">Copiar</button>
    <button type="button" onclick="limpar()" class="btn btn-danger">Limpar</button>
    <br><br>
    
    <!--<a href="https://unicode-table.com/pt/blocks/cyrillic/" target="_blank" rel="noopener noreferrer"><h4>Referências para entender como funciona</h4></a>-->
  </div>

  <script type="text/javascript">
    function limpar(){
      document.getElementById("cript").innerHTML = '';
      document.getElementById("campo").value = '';
    }
   
    function copiarHtml() {
        document.getElementById("cript").select(); 
        document.execCommand('copy');
    }
  </script> 
  </div>
    </div>
</div>