import * as React from 'react';
const SvgIconSoundwave = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M28 6v48M36 10v48M12 24v16M20 18v28M4 30v4M60 30v4M44 18v28M52 26v12"
		/>
	</svg>
);
export default SvgIconSoundwave;
