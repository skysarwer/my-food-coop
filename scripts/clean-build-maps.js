const fs = require( 'fs' );
const path = require( 'path' );

const buildDir = path.join( __dirname, '..', 'build' );

function removeMapFiles( dirPath ) {
	for ( const entry of fs.readdirSync( dirPath, { withFileTypes: true } ) ) {
		const entryPath = path.join( dirPath, entry.name );

		if ( entry.isDirectory() ) {
			removeMapFiles( entryPath );
			continue;
		}

		if ( entry.isFile() && entry.name.endsWith( '.map' ) ) {
			fs.unlinkSync( entryPath );
		}
	}
}

if ( fs.existsSync( buildDir ) ) {
	removeMapFiles( buildDir );
}
