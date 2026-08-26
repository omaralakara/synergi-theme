/*
 * audit.js — the structural rules in CLAUDE.md, checked over the theme.
 *
 * Not part of the theme (see tools/README.md): nothing in synergi/ requires it
 * and the site runs identically if this folder is deleted. It exists so "is the
 * structure still right?" is a command rather than an afternoon of grepping.
 *
 *   node tools/audit.js
 *
 * Exits non-zero if anything fails, so it can gate a stage.
 */

const fs = require( 'fs' );
const path = require( 'path' );

const REPO = path.join( __dirname, '..' );
const ROOT = path.join( REPO, 'synergi' );
const rel = ( p ) => path.relative( REPO, p ).split( path.sep ).join( '/' );

let problems = 0;
const fail = ( m ) => {
	console.log( '  FAIL  ' + m );
	problems++;
};
const pass = ( m ) => console.log( '  ok    ' + m );

function walk( dir, ext, out ) {
	out = out || [];

	for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		const p = path.join( dir, entry.name );

		if ( entry.isDirectory() ) {
			walk( p, ext, out );
		} else if ( p.endsWith( ext ) ) {
			out.push( p );
		}
	}

	return out;
}

const sectionsDir = path.join( ROOT, 'sections' );
const cssDir = path.join( ROOT, 'assets', 'css', 'sections' );
const jsDir = path.join( ROOT, 'assets', 'js', 'sections' );

const sections = fs.readdirSync( sectionsDir ).filter( ( f ) => f.endsWith( '.php' ) ).map( ( f ) => f.slice( 0, -4 ) );
const cssFiles = [ path.join( ROOT, 'assets', 'css', 'base.css' ) ]
	.concat( fs.readdirSync( cssDir ).map( ( f ) => path.join( cssDir, f ) ) );
const jsFiles = [ path.join( ROOT, 'assets', 'js', 'main.js' ) ]
	.concat( fs.readdirSync( jsDir ).map( ( f ) => path.join( jsDir, f ) ) );

const uncomment = ( src ) => src.replace( /\/\*[\s\S]*?\*\//g, ( m ) => m.replace( /[^\n]/g, ' ' ) );

/* ---------------------------------------------------------------- */
console.log( '\n1. Section trios — php + css, matching names (CLAUDE.md §4)' );
{
	let bad = 0;

	sections.forEach( ( s ) => {
		if ( ! fs.existsSync( path.join( cssDir, s + '.css' ) ) ) {
			fail( s + ': partial with no stylesheet' );
			bad++;
		}
	} );

	fs.readdirSync( cssDir ).forEach( ( f ) => {
		if ( sections.indexOf( f.slice( 0, -4 ) ) < 0 ) {
			fail( 'stylesheet with no partial: ' + f );
			bad++;
		}
	} );

	fs.readdirSync( jsDir ).forEach( ( f ) => {
		if ( sections.indexOf( f.slice( 0, -3 ) ) < 0 ) {
			fail( 'script with no partial: ' + f );
			bad++;
		}
	} );

	if ( ! bad ) {
		pass( sections.length + ' sections, every one a matched trio, no orphans' );
	}
}

/* ---------------------------------------------------------------- */
console.log( '\n2. Declared sections match rendered ones (CLAUDE.md §4)' );
{
	const tpl = fs.readFileSync( path.join( ROOT, 'templates', 'homepage.php' ), 'utf8' );
	const declaredBlock = tpl.match( /syn_use_sections\(\s*array\(([^)]*)\)/ );
	const declared = declaredBlock ? declaredBlock[ 1 ].split( ',' ).map( ( x ) => x.trim().replace( /^'|'$/g, '' ) ).filter( Boolean ) : [];
	const rendered = [];

	for ( const m of tpl.matchAll( /syn_section\(\s*'([a-z0-9-]+)'/g ) ) {
		rendered.push( m[ 1 ] );
	}

	const missing = declared.filter( ( x ) => rendered.indexOf( x ) < 0 );
	const extra = rendered.filter( ( x ) => declared.indexOf( x ) < 0 );

	// Declaration order must match render order too, or the page reads in one
	// order and loads its stylesheets in another.
	const orderOk = declared.join( ',' ) === rendered.join( ',' );

	if ( missing.length ) {
		fail( 'declared but never rendered: ' + missing.join( ', ' ) );
	}

	if ( extra.length ) {
		fail( 'rendered but never declared (its assets never load): ' + extra.join( ', ' ) );
	}

	if ( ! missing.length && ! extra.length && ! orderOk ) {
		fail( 'declared order differs from render order' );
	}

	if ( ! missing.length && ! extra.length && orderOk ) {
		pass( declared.length + ' sections declared and rendered, same order' );
	}
}

/* ---------------------------------------------------------------- */
console.log( '\n3. ABSPATH guard and syn_ prefixes (CLAUDE.md §4)' );
{
	let bad = 0;

	walk( ROOT, '.php' ).forEach( ( f ) => {
		const src = fs.readFileSync( f, 'utf8' );

		if ( src.indexOf( "defined( 'ABSPATH' ) || exit;" ) < 0 ) {
			fail( 'no ABSPATH guard: ' + rel( f ) );
			bad++;
		}

		/*
		 * Inline <script> blocks are stripped first. The rule is about PHP
		 * functions; a JavaScript one inside an IIFE — inc/integrations.php has
		 * one — is already scoped to that IIFE and follows JavaScript's naming,
		 * not PHP's.
		 */
		const php = src.replace( /<script[\s\S]*?<\/script>/gi, '' );

		for ( const m of php.matchAll( /^[ \t]*function[ \t]+([A-Za-z0-9_]+)/gm ) ) {
			if ( m[ 1 ].indexOf( 'syn_' ) !== 0 ) {
				fail( 'function without a syn_ prefix: ' + m[ 1 ] + ' in ' + rel( f ) );
				bad++;
			}
		}
	} );

	if ( ! bad ) {
		pass( 'every PHP file guarded, every function prefixed' );
	}
}

/* ---------------------------------------------------------------- */
console.log( '\n4. No colour literals, no physical directions (CLAUDE.md §2.7, §2.11)' );
{
	let bad = 0;

	cssFiles.forEach( ( f ) => {
		uncomment( fs.readFileSync( f, 'utf8' ) ).split( '\n' ).forEach( ( line, i ) => {
			if ( /#[0-9a-fA-F]{3,8}\b/.test( line ) || /\brgba?\(/.test( line ) ) {
				fail( 'colour literal at ' + rel( f ) + ':' + ( i + 1 ) );
				bad++;
			}

			// transform-origin has no logical form, so it is allowed to name a side.
			if ( /transform-origin/.test( line ) ) {
				return;
			}

			if ( /(^|[^-\w])(margin|padding|border)-(left|right|top|bottom)\s*:/.test( line ) ||
				/(^|[^-\w])(left|right|top|bottom)\s*:/.test( line ) ) {
				fail( 'physical direction at ' + rel( f ) + ':' + ( i + 1 ) );
				bad++;
			}
		} );
	} );

	if ( ! bad ) {
		pass( 'no hex or rgba, no left/right/top/bottom in any stylesheet' );
	}
}

/* ---------------------------------------------------------------- */
console.log( '\n5. Every !important is explained (CLAUDE.md §2.12)' );
{
	let total = 0;
	let bare = 0;

	cssFiles.forEach( ( f ) => {
		const lines = fs.readFileSync( f, 'utf8' ).split( '\n' );

		lines.forEach( ( line, i ) => {
			if ( ! /!important/.test( line ) || /^\s*\*/.test( line ) ) {
				return;
			}

			total++;

			const preceding = lines.slice( Math.max( 0, i - 10 ), i ).join( '\n' );

			if ( ! /\/\*|^\s*\*/m.test( preceding ) ) {
				fail( 'unexplained !important at ' + rel( f ) + ':' + ( i + 1 ) );
				bare++;
			}
		} );
	} );

	if ( ! bare ) {
		pass( total + ' !important declarations, each with a reason above it' );
	}
}

/* ---------------------------------------------------------------- */
console.log( '\n6. Each class defined in exactly one stylesheet (CLAUDE.md §13)' );
{
	/*
	 * "Defined" means the class leads a selector at the start of a line. A
	 * section scoping a shared component in context (".syn-numbers .syn-eyebrow")
	 * is not a second definition — base.css is still the one place it is made.
	 */
	const defs = {};

	cssFiles.forEach( ( f ) => {
		const src = fs.readFileSync( f, 'utf8' ).replace( /\/\*[\s\S]*?\*\//g, '' );

		for ( const m of src.matchAll( /^[ \t]*\.(syn-[a-z0-9_-]+)(?![\w-])[^{}]*\{/gm ) ) {
			defs[ m[ 1 ] ] = defs[ m[ 1 ] ] || new Set();
			defs[ m[ 1 ] ].add( path.basename( f ) );
		}
	} );

	let bad = 0;

	Object.keys( defs ).forEach( ( c ) => {
		if ( defs[ c ].size > 1 ) {
			fail( c + ' is defined in ' + Array.from( defs[ c ] ).join( ' and ' ) );
			bad++;
		}
	} );

	if ( ! bad ) {
		pass( Object.keys( defs ).length + ' classes, each defined in exactly one file' );
	}
}

/* ---------------------------------------------------------------- */
console.log( '\n7. Every class used in a partial has a rule (CLAUDE.md §13)' );
{
	const allCss = cssFiles.map( ( f ) => fs.readFileSync( f, 'utf8' ) ).join( '\n' );
	let bad = 0;

	sections.forEach( ( s ) => {
		const src = fs.readFileSync( path.join( sectionsDir, s + '.php' ), 'utf8' );
		const used = new Set();

		for ( const m of src.matchAll( /class="([^"$<]*)"/g ) ) {
			m[ 1 ].split( /\s+/ ).filter( Boolean ).forEach( ( c ) => used.add( c ) );
		}

		// Classes assembled in PHP, e.g. $x ? 'syn-a syn-b' : 'syn-a'. A trailing
		// dash means it is a wp_unique_id() prefix, not a class.
		for ( const m of src.matchAll( /'(syn-[a-z0-9_-]*(?: syn-[a-z0-9_-]+)*)'/g ) ) {
			m[ 1 ].split( /\s+/ ).forEach( ( c ) => {
				if ( c.slice( -1 ) !== '-' ) {
					used.add( c );
				}
			} );
		}

		used.forEach( ( c ) => {
			if ( c.indexOf( 'syn-' ) !== 0 ) {
				return;
			}

			if ( ! new RegExp( '\\.' + c.replace( /-/g, '\\-' ) + '(?![\\w-])' ).test( allCss ) ) {
				fail( c + ' is used in ' + s + '.php but has no rule anywhere' );
				bad++;
			}
		} );
	} );

	if ( ! bad ) {
		pass( 'every class in every partial resolves to a rule' );
	}
}

/* ---------------------------------------------------------------- */
console.log( '\n8. Payload budget (CLAUDE.md §6)' );
{
	// Comments and indentation stripped, which is roughly what the host's
	// minifier does. Not a substitute for measuring a real page.
	const strip = ( s ) => Buffer.byteLength( s.replace( /\/\*[\s\S]*?\*\//g, '' ).replace( /^\s+/gm, '' ).replace( /\n+/g, '\n' ) );
	const cssKB = cssFiles.reduce( ( t, f ) => t + strip( fs.readFileSync( f, 'utf8' ) ), 0 ) / 1024;
	const jsKB = jsFiles.reduce( ( t, f ) => t + strip( fs.readFileSync( f, 'utf8' ) ), 0 ) / 1024;

	( cssKB < 120 ? pass : fail )( 'CSS ' + cssKB.toFixed( 1 ) + ' KB of 120 (whole theme, before conditional loading)' );
	( jsKB < 200 ? pass : fail )( 'JS  ' + jsKB.toFixed( 1 ) + ' KB of 200 (whole theme, before conditional loading)' );
}

console.log( '\n' + ( problems ? problems + ' problem(s) found.' : 'All checks pass.' ) + '\n' );
process.exit( problems ? 1 : 0 );
