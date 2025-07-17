const text = '$$\\begin{pmatrix}a&b\\\\c&d\\end{pmatrix}$$';

function quotePattern(p){
  return p.replace(/([.*+?^${}()|[\]\/\\])/g,'\\$1');
}

function findTeX(text){
  const startRegex = /(\$\$)|\\begin\s*\{([^}]*)\}/g;
  function findEnd(start){
    if(start[1]){
      const endPattern = new RegExp(`${quotePattern('$$')}|\\\\(?:[a-zA-Z]|.)|[{}]`,'g');
      endPattern.lastIndex = start.index + start[0].length;
      let match, braces=0;
      while((match=endPattern.exec(text))){
        if(match[0]==='$$' && braces===0){
          return text.slice(start.index, match.index+2);
        }else if(match[0]==='{'){braces++;}else if(match[0]==='}'&&braces){braces--;}
      }
    } else {
      const env=start[2];
      const pattern=new RegExp(`\\\\(?:begin|end)\\s*\\{${quotePattern(env)}\\}|\\\\(?:[a-zA-Z]|.)|[{}]`,'g');
      pattern.lastIndex=start.index+start[0].length;
      let match,braces=0,nested=0;
      while((match=pattern.exec(text))){
        if(match[0].startsWith('\\begin')){nested++;}
        else if(match[0].startsWith('\\end') && nested){nested--;}
        else if(match[0].startsWith('\\end') && nested===0){
          return text.slice(start.index, match.index + match[0].length);
        }else if(match[0]==='{'){braces++;}else if(match[0]==='}'&&braces){braces--;}
      }
    }
    return null;
  }
  let m; const items=[]; startRegex.lastIndex=0;
  while((m=startRegex.exec(text))){
    const block=findEnd(m);
    if(block){items.push(block); startRegex.lastIndex = m.index + block.length;}
  }
  return items;
}

console.log(findTeX(text));
