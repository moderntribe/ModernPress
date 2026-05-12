import * as React from 'react';
const SvgIconPhotoImage = ( props ) => (
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
			d="m12 50 8-14 8 8 12-16 12 22zM25 26a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M54 4H10a6 6 0 0 0-6 6v44a6 6 0 0 0 6 6h44a6 6 0 0 0 6-6V10a6 6 0 0 0-6-6Z"
		/>
	</svg>
);
export default SvgIconPhotoImage;
